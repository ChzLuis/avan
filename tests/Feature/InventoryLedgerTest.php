<?php

namespace Tests\Feature;

use App\Modules\Inventario\Models\InventoryMovement;
use App\Modules\Ventas\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Modules\Inventario\Support\InventoryLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invariantes del Kardex.
 *
 * La regla que se protege: products.stock y el historial de movimientos NUNCA
 * pueden discrepar. Si alguien vuelve a mover stock sin pasar por el ledger, el
 * saldo deja de cuadrar y el historial pierde todo su valor.
 */
class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio kardex',
            'slug'      => 'negocio-kardex',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'project_id' => $this->project->id, 'name' => 'Martillo', 'price' => 30, 'cost' => 18, 'stock' => 10,
        ]);
    }

    public function test_cada_movimiento_deja_el_saldo_igual_al_stock(): void
    {
        InventoryLedger::registrar($this->product, 5, 'compra', 18.0);
        InventoryLedger::registrar($this->product, -3, 'venta');

        $ultimo = InventoryMovement::where('product_id', $this->product->id)->latest('id')->first();

        $this->assertSame(12, (int) $this->product->fresh()->stock);
        $this->assertSame(12, (int) $ultimo->balance_after, 'El saldo del Kardex no coincide con el stock real.');
    }

    public function test_ajustar_a_fija_la_cantidad_exacta_y_registra_la_diferencia(): void
    {
        $mov = InventoryLedger::ajustarA($this->product, 4, 'conteo', 'Conteo de agosto');

        $this->assertSame(4, (int) $this->product->fresh()->stock);
        $this->assertSame(-6, (int) $mov->quantity, 'El conteo debe registrar la diferencia, no la cantidad final.');
    }

    public function test_un_producto_sin_control_de_stock_no_genera_kardex(): void
    {
        $servicio = Product::create([
            'project_id' => $this->project->id, 'name' => 'Instalación', 'price' => 50, 'stock' => null,
        ]);

        $this->assertNull(InventoryLedger::registrar($servicio, -1, 'venta'));
        $this->assertSame(0, InventoryMovement::where('product_id', $servicio->id)->count());
    }

    public function test_ajustar_sin_cambio_real_no_ensucia_el_historial(): void
    {
        $this->assertNull(InventoryLedger::ajustarA($this->product, 10, 'conteo'));
        $this->assertSame(0, InventoryMovement::where('product_id', $this->product->id)->count());
    }

    /**
     * Anular un pedido devuelve al almacen exactamente lo que ese pedido saco, y
     * hacerlo dos veces no repone de mas. Sin esto cada anulacion era una fuga
     * permanente de inventario.
     */
    public function test_anular_un_pedido_devuelve_el_stock_una_sola_vez(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente', 'status' => 'pending', 'total' => 60,
        ]);

        InventoryLedger::registrar($this->product, -2, 'venta', null, 'Venta', 'order', $order->id);
        $this->assertSame(8, (int) $this->product->fresh()->stock);

        // Dos veces: la segunda no debe reponer nada.
        foreach ([1, 2] as $_) {
            $yaDevuelto = InventoryMovement::where('reference_type', 'order_cancel')
                ->where('reference_id', $order->id)->exists();
            if ($yaDevuelto) continue;

            foreach (InventoryMovement::where('reference_type', 'order')->where('reference_id', $order->id)
                         ->where('quantity', '<', 0)->get() as $mov) {
                InventoryLedger::registrar(
                    Product::find($mov->product_id), abs((int) $mov->quantity), 'anulacion',
                    null, 'Devolución', 'order_cancel', $order->id
                );
            }
        }

        $this->assertSame(10, (int) $this->product->fresh()->stock, 'La anulación debe dejar el stock como antes de la venta.');
        $this->assertSame(1, InventoryMovement::where('reference_type', 'order_cancel')->count());
    }
}
