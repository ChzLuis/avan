<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cierre de RISK-012: un único camino transaccional de numeración. La reserva
 * del correlativo y la creación del comprobante son atómicas, de modo que dos
 * emisiones no obtienen el mismo número, y un fallo no deja un hueco.
 */
class InvoiceNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Num QA', 'slug' => 'num-qa', 'category' => 'retail', 'is_active' => true,
        ]);
    }

    private function emitir(string $serie = 'F001'): Invoice
    {
        return Invoice::emitir($this->project->id, 'factura', $serie, null,
            function (int $correlativo, string $numero) use ($serie) {
                return $this->project->invoices()->create([
                    'type' => 'factura', 'serie' => $serie, 'correlativo' => $correlativo,
                    'numero' => $numero, 'client_name' => 'C', 'subtotal' => '10.00',
                    'igv' => '1.80', 'total' => '11.80', 'currency' => 'PEN',
                    'issue_date' => now()->toDateString(), 'status' => 'issued',
                ]);
            });
    }

    public function test_dos_emisiones_de_la_misma_serie_no_repiten_numero(): void
    {
        $a = $this->emitir();
        $b = $this->emitir();

        $this->assertSame(1, $a->correlativo);
        $this->assertSame(2, $b->correlativo);
        $this->assertNotSame($a->numero, $b->numero);
        $this->assertSame('F001-00000001', $a->numero);
        $this->assertSame('F001-00000002', $b->numero);
    }

    public function test_series_distintas_llevan_correlativos_independientes(): void
    {
        $this->emitir('F001');
        $b = $this->emitir('F002');

        // F002 empieza en 1, no continúa el correlativo de F001.
        $this->assertSame(1, $b->correlativo);
    }

    public function test_un_fallo_en_la_creacion_no_reserva_el_numero(): void
    {
        // Si el closure revienta, la transacción revierte y el correlativo no
        // se consume: la siguiente emisión sigue tomando el número 1.
        try {
            Invoice::emitir($this->project->id, 'factura', 'F001', null,
                fn (int $c, string $n) => throw new \RuntimeException('boom'));
        } catch (\RuntimeException $e) {
            // esperado
        }

        $this->assertSame(0, $this->project->invoices()->count());
        $this->assertSame(1, $this->emitir()->correlativo, 'no debe haber hueco');
    }

    public function test_ningun_controlador_reserva_el_correlativo_fuera_de_transaccion(): void
    {
        // Barrido estático: sólo el modelo (emitirNumero/emitir) puede llamar
        // nextCorrelativo; ningún controlador debe hacerlo crudo.
        $controllers = glob(app_path('Http/Controllers/**/*.php'), GLOB_BRACE)
            + glob(app_path('Http/Controllers/*.php'));
        foreach (array_unique($controllers) as $file) {
            $this->assertStringNotContainsString('nextCorrelativo', (string) file_get_contents($file),
                basename($file).' llama nextCorrelativo crudo — usa Invoice::emitir');
        }
    }
}
