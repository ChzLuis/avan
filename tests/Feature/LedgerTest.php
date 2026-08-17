<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * F2b — Contratos del libro de cobros.
 *
 * Lo que se protege: el saldo se DERIVA del libro (nunca se acumula), no se
 * puede sobrecobrar, un cobro no se edita sino que se revierte, y el vencido
 * es una fecha pactada y no "dias desde que se creo".
 */
class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Libro QA', 'slug' => 'libro-qa', 'category' => 'retail', 'is_active' => true,
        ]);
    }

    private function pedido(string $total = '1000.00'): Order
    {
        return Order::create([
            'project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => $total,
        ]);
    }

    public function test_el_saldo_se_deriva_del_libro_y_proyecta_el_documento(): void
    {
        $o = $this->pedido('1000.00');

        Ledger::registrar($this->project, $o, 30050);   // 300.50
        Ledger::registrar($this->project, $o, 19950);   // 199.50

        $this->assertSame(50000, Ledger::cobradoCents($this->project->id, 'order', $o->id));
        $this->assertSame(50000, Ledger::saldoCents($this->project->id, $o->fresh()));

        $o->refresh();
        $this->assertSame('partial', $o->payment_status, 'la proyeccion debe reflejar el pago parcial');
        $this->assertSame('500.00', (string) $o->advance_amount);
    }

    /** Dos abonos parciales dejan DOS asientos: antes eran indistinguibles. */
    public function test_cada_cobro_queda_registrado_por_separado(): void
    {
        $o = $this->pedido('1000.00');
        Ledger::registrar($this->project, $o, 30000, 'yape', 'OP-1');
        Ledger::registrar($this->project, $o, 20000, 'efectivo', 'CAJA-9');

        $asientos = Payment::where('payable_id', $o->id)->orderBy('id')->get();
        $this->assertCount(2, $asientos);
        $this->assertSame(['yape', 'efectivo'], $asientos->pluck('method')->all());
        $this->assertSame(['OP-1', 'CAJA-9'], $asientos->pluck('reference')->all());
    }

    public function test_no_se_puede_cobrar_mas_que_el_total(): void
    {
        $o = $this->pedido('100.00');
        Ledger::registrar($this->project, $o, 9000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/excede el saldo/i');
        Ledger::registrar($this->project, $o, 2000);   // 90 + 20 > 100
    }

    public function test_el_pago_completo_marca_paid(): void
    {
        $o = $this->pedido('250.00');
        Ledger::registrar($this->project, $o, 25000);

        $this->assertSame('paid', $o->fresh()->payment_status);
        $this->assertSame(0, Ledger::saldoCents($this->project->id, $o->fresh()));
    }

    /** Revertir NO borra: inserta, y el saldo vuelve solo. */
    public function test_revertir_conserva_el_asiento_original(): void
    {
        $o = $this->pedido('500.00');
        $cobro = Ledger::registrar($this->project, $o, 50000);
        $this->assertSame('paid', $o->fresh()->payment_status);

        Ledger::revertir($cobro, 'voucher falso');

        $this->assertDatabaseHas('payments', ['id' => $cobro->id, 'reverses_id' => null]);
        $this->assertSame(0, Ledger::cobradoCents($this->project->id, 'order', $o->id));
        $this->assertSame('pending', $o->fresh()->payment_status,
            'al revertir, la proyeccion vuelve a pendiente');
        $this->assertSame(2, Payment::where('payable_id', $o->id)->count(),
            'la reversion es un asiento mas, no un borrado');
    }

    public function test_un_cobro_no_se_puede_revertir_dos_veces(): void
    {
        $o = $this->pedido('500.00');
        $cobro = Ledger::registrar($this->project, $o, 10000);
        Ledger::revertir($cobro, 'error');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ya fue revertido/i');
        Ledger::revertir($cobro, 'otra vez');
    }

    public function test_tras_revertir_se_puede_volver_a_cobrar_el_total(): void
    {
        $o = $this->pedido('100.00');
        $cobro = Ledger::registrar($this->project, $o, 10000);
        Ledger::revertir($cobro, 'se equivoco de pedido');

        Ledger::registrar($this->project, $o, 10000);   // no debe chocar con el tope
        $this->assertSame('paid', $o->fresh()->payment_status);
    }

    public function test_no_se_admiten_importes_cero_o_negativos(): void
    {
        $o = $this->pedido('100.00');
        $this->expectException(RuntimeException::class);
        Ledger::registrar($this->project, $o, 0);
    }

    /**
     * El nucleo de F2b: el vencimiento es una fecha pactada, configurable por
     * negocio. Antes, CxC llamaba vencido a todo lo que pasara de 15 dias
     * desde su creacion, asi que una venta a 30 dias salia morosa el dia 16.
     */
    public function test_el_vencimiento_sale_del_ajuste_del_negocio(): void
    {
        $o = $this->pedido('1000.00');
        $o->forceFill(['created_at' => Carbon::parse('2026-08-01')])->save();

        // Negocio al contado (por defecto): vence el mismo dia.
        $contado = Ledger::generarVencimiento($this->project, $o->fresh());
        $this->assertSame('2026-08-01', $contado->due_date->toDateString());

        // El mismo documento en un negocio que fia a 30 dias.
        $this->project->settings()->create(['key' => 'cxc_plazo_dias', 'value' => '30']);
        $credito = Ledger::generarVencimiento($this->project->fresh(), $o->fresh());
        $this->assertSame('2026-08-31', $credito->due_date->toDateString());

        // Y el dia 16 —que la logica vieja daba por vencido— aun no lo esta.
        $this->assertFalse($credito->vencido(Carbon::parse('2026-08-16')),
            'a 30 dias de credito, el dia 16 NO esta vencido');
        $this->assertTrue($credito->vencido(Carbon::parse('2026-09-01')));
        $this->assertSame(1, $credito->diasDeAtraso(Carbon::parse('2026-09-01')));
    }

    public function test_la_reversion_deja_rastro_en_la_bitacora(): void
    {
        $o = $this->pedido('300.00');
        $cobro = Ledger::registrar($this->project, $o, 30000);
        Ledger::revertir($cobro, 'duplicado');

        $this->assertDatabaseHas('order_events', ['order_id' => $o->id, 'action' => 'payment_recorded']);
        $this->assertDatabaseHas('order_events', ['order_id' => $o->id, 'action' => 'payment_reversed']);
    }
}
