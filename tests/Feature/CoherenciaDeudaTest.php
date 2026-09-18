<?php

namespace Tests\Feature;

use App\Modules\Ventas\Models\Order;
use App\Modules\Finanzas\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Modules\Finanzas\Support\Cobranza;
use App\Modules\Finanzas\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cierre de RISK-010: una sola verdad para la deuda. Todo pago pasa por el
 * Ledger; Cuentas por Cobrar (columna proyectada) y Customer 360 (libro)
 * derivan del mismo libro y NUNCA divergen.
 */
class CoherenciaDeudaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Deuda QA', 'slug' => 'deuda-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $this->order = $this->project->orders()->create([
            'client_name' => 'Cliente', 'client_phone' => '999', 'status' => 'pending',
            'total' => 100, 'sales_channel' => 'web',
        ]);
    }

    /** Deuda según Cuentas por Cobrar (columna proyectada por el Ledger). */
    private function deudaCartera(): float
    {
        $fila = collect(Cobranza::cartera($this->project))['filas'] ?? [];
        foreach (Cobranza::cartera($this->project)['filas'] ?? [] as $f) {
            if (($f['tipo'] ?? null) === 'pedido' && ($f['id'] ?? null) === $this->order->id) {
                return (float) $f['saldo'];
            }
        }
        return 0.0; // no aparece = saldado
    }

    /** Deuda según el libro (lo que usa Customer 360). */
    private function deudaLibro(): float
    {
        $cobrado = Ledger::cobradoCents($this->project->id, 'order', $this->order->id);
        return round(max(0, 10000 - $cobrado) / 100, 2);
    }

    private function assertAmbasFuentes(float $esperada): void
    {
        $this->assertEqualsWithDelta($esperada, $this->deudaLibro(), 0.001, 'Customer 360 (libro)');
        $this->assertEqualsWithDelta($esperada, $this->deudaCartera(), 0.001, 'Cuentas por Cobrar');
    }

    public function test_la_deuda_es_identica_en_cartera_y_en_360_en_todo_el_ciclo(): void
    {
        // Pago 0 → deuda 100 en ambas.
        $this->assertAmbasFuentes(100.0);

        // Pago 30 → deuda 70.
        Ledger::registrar($this->project, $this->order, 3000, 'manual', 'op-1', 'checkout');
        $this->assertAmbasFuentes(70.0);

        // Pago 70 adicional → deuda 0.
        $pago2 = Ledger::registrar($this->project, $this->order, 7000, 'manual', 'op-2', 'checkout');
        $this->assertAmbasFuentes(0.0);

        // Reversión de 30 → deuda 30 en ambas.
        Ledger::revertir(Payment::where('reference', 'op-1')->firstOrFail(), 'ajuste');
        $this->assertAmbasFuentes(30.0);
    }

    public function test_un_pago_de_pasarela_por_el_controlador_deja_la_deuda_coherente(): void
    {
        // El checkout confirma un pago manual (número de operación). Antes esto
        // marcaba payment_status='paid' sin asiento → cartera 0 / 360 = 100.
        $this->post("/{$this->project->slug}/pay/{$this->order->id}/manual", [
            'reference' => 'YAPE-123',
        ])->assertOk();

        // Un solo asiento por el total; ambas fuentes en 0, sin divergencia.
        $this->assertSame(1, Payment::where('payable_id', $this->order->id)->count());
        $this->assertAmbasFuentes(0.0);
    }

    public function test_un_segundo_reporte_del_mismo_pago_no_duplica_el_asiento(): void
    {
        // Idempotencia: doble submit / webhook repetido no crea dos cobros.
        $this->post("/{$this->project->slug}/pay/{$this->order->id}/manual", ['reference' => 'YAPE-1'])->assertOk();
        $this->post("/{$this->project->slug}/pay/{$this->order->id}/manual", ['reference' => 'YAPE-1'])->assertOk();

        $this->assertSame(1, Payment::where('payable_id', $this->order->id)->count());
        $this->assertAmbasFuentes(0.0);
    }
}
