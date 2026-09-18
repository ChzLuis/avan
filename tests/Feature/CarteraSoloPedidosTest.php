<?php

namespace Tests\Feature;

use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use App\Modules\Ventas\Models\Quote;
use App\Modules\Finanzas\Models\ReceivableTerm;
use App\Models\User;
use App\Modules\Finanzas\Support\Cobranza;
use App\Modules\Finanzas\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La cartera de cobro es de PEDIDOS.
 *
 * Una cotización es una oferta: no genera derecho de cobro hasta convertirse
 * en pedido. Es el criterio de Odoo, SAP B1, Dynamics y NetSuite, y la razón
 * es contable — un adelanto sobre una oferta es un pasivo (dinero que se debe
 * devolver si no hay venta), no una cuenta por cobrar.
 *
 * Cuando el libro admitía cotizaciones, el "por cobrar" del negocio incluía
 * plata que nadie debía. Este contrato impide que vuelva a entrar por ahí.
 */
class CarteraSoloPedidosTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Cartera QA', 'slug' => 'cartera-qa', 'category' => 'retail', 'is_active' => true,
        ]);
    }

    /** El libro acepta pedidos: ese es su trabajo. */
    public function test_un_pedido_entra_al_libro(): void
    {
        $order = Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'total' => '100.00']);

        Ledger::registrar($this->project, $order, 4000, 'efectivo', null, 'panel');

        $this->assertSame(4000, Ledger::cobradoCents($this->project->id, 'order', $order->id));
        $this->assertSame(6000, Ledger::saldoCents($this->project->id, $order->fresh()));
        $this->assertSame('partial', $order->fresh()->payment_status);
    }

    /**
     * Y rechaza cotizaciones por tipo, no por una comprobación que alguien
     * pueda olvidar: el error salta al escribir el código.
     */
    public function test_una_cotizacion_no_puede_entrar_al_libro(): void
    {
        $quote = Quote::create(['project_id' => $this->project->id, 'client_name' => 'B',
            'status' => 'accepted', 'total' => '500.00']);

        $this->expectException(\TypeError::class);
        Ledger::registrar($this->project, $quote, 10000, 'efectivo', null, 'panel');
    }

    /** Tampoco se le puede fijar un vencimiento de cobro. */
    public function test_una_cotizacion_no_recibe_vencimiento(): void
    {
        $quote = Quote::create(['project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'accepted', 'total' => '500.00']);

        $this->expectException(\TypeError::class);
        Ledger::generarVencimiento($this->project, $quote);
    }

    /**
     * La cartera que se enseña en pantalla solo suma pedidos. Aunque en la
     * base quedara un vencimiento de cotización de antes de esta decisión,
     * no debe aparecer en el total por cobrar.
     */
    public function test_la_cartera_ignora_los_vencimientos_de_cotizacion(): void
    {
        $order = Order::create(['project_id' => $this->project->id, 'client_name' => 'D',
            'status' => 'pending', 'total' => '200.00']);
        Ledger::generarVencimiento($this->project, $order);

        $quote = Quote::create(['project_id' => $this->project->id, 'client_name' => 'E',
            'status' => 'accepted', 'total' => '9999.00']);
        // Residuo del diseño anterior, insertado a mano como lo hay en ARIN.
        ReceivableTerm::create([
            'project_id' => $this->project->id, 'payable_type' => 'quote',
            'payable_id' => $quote->id, 'numero' => 1,
            'due_date' => now()->subDays(30), 'amount_cents' => 999900,
        ]);

        $cartera = Cobranza::cartera($this->project);

        // Solo el pedido: los 9 999 de la cotización no son deuda de nadie.
        $this->assertSame(20000, $cartera['total_cents']);
        foreach ($cartera['filas'] as $fila) {
            $this->assertNotSame('quote', $fila['tipo'] ?? 'order');
        }
    }
}
