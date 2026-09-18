<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El mostrador no cobra dos veces (2026-09-11).
 *
 * `processing` protegia el clic, pero no la peticion: doble toque con red
 * lenta, recarga a medio cobro o reintento del navegador creaban DOS ventas
 * con su stock descontado dos veces. Comprobantes y guias ya tenian huella de
 * idempotencia; el POS, que es donde mas prisa hay, no.
 */
class PosCobroSeguroTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;
    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA', 'slug' => 'poc-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['orders', 'catalog'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        $this->producto = Product::create([
            'project_id' => $this->project->id, 'name' => 'CABLE TW-80', 'price' => 100,
            'stock' => 10, 'is_available' => true,
        ]);
    }

    private function venta(): array
    {
        return [
            'payment_method' => 'efectivo',
            'items' => [[
                'product_id' => $this->producto->id, 'name' => 'CABLE TW-80',
                'price' => 100, 'quantity' => 2,
            ]],
        ];
    }

    private function cobrar(array $cabeceras = [])
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id])
            ->withHeaders($cabeceras)
            ->postJson(route('pos.store', $this->project), $this->venta());
    }

    /** Dos toques con la misma huella = UNA venta y el stock baja una vez. */
    public function test_dos_toques_no_cobran_dos_veces(): void
    {
        $huella = ['X-Idempotencia' => 'venta-doble-toque'];

        $uno = $this->cobrar($huella)->assertOk();
        $dos = $this->cobrar($huella)->assertOk();

        $this->assertSame(1, Order::where('project_id', $this->project->id)->count(), 'solo debe existir UNA venta');
        $this->assertTrue((bool) $dos->json('repetida'), 'la segunda devuelve la misma venta');
        $this->assertSame($uno->json('order.id'), $dos->json('order.id'));
        $this->assertSame(8, (int) $this->producto->fresh()->stock, 'el stock baja una sola vez');
    }

    /** Dos ventas DISTINTAS sí se cobran las dos: la protección no estorba. */
    public function test_dos_ventas_distintas_si_se_cobran(): void
    {
        $this->cobrar(['X-Idempotencia' => 'venta-1'])->assertOk();
        $this->cobrar(['X-Idempotencia' => 'venta-2'])->assertOk();

        $this->assertSame(2, Order::where('project_id', $this->project->id)->count());
        $this->assertSame(6, (int) $this->producto->fresh()->stock);
    }

    /** Sin huella (un cliente viejo) se sigue cobrando: no se rompe nada. */
    public function test_sin_huella_se_cobra_igual(): void
    {
        $this->cobrar()->assertOk();

        $this->assertSame(1, Order::where('project_id', $this->project->id)->count());
    }
}
