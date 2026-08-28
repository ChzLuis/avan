<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El precio del checkout publico lo pone el CATALOGO, nunca el comprador.
 *
 * `POST /{slug}/order` es publico —sin sesion ni permiso— y aceptaba
 * `items.*.price` del cuerpo de la peticion: solo consultaba la base
 * `if ($pid && (!$name || !$price))`, asi que enviando nombre Y precio ganaba
 * el del cliente. Un `{"product_id":7,"name":"Laptop","price":0.01}` compraba
 * a un centimo, el stock se descontaba de verdad y el pedido entraba en
 * Cuentas por Cobrar como bueno. Era el canal de mas volumen y no tenia ni un
 * solo test.
 */
class CheckoutPublicoPrecioTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda QA', 'slug' => 'tienda-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $this->producto = $this->project->products()->create([
            'name' => 'Laptop Lenovo', 'price' => '2799.00',
            'stock' => 10, 'is_available' => true,
        ]);
    }

    private function pedir(array $items, array $extra = [])
    {
        return $this->postJson("/{$this->project->slug}/order", array_merge([
            'client_name'  => 'Comprador',
            'client_phone' => '987654321',
            'items'        => $items,
        ], $extra));
    }

    /** El ataque exacto: mandar el precio que uno quiera. */
    public function test_el_precio_que_manda_el_comprador_se_ignora(): void
    {
        $r = $this->pedir([[
            'product_id' => $this->producto->id,
            'name'       => 'Laptop Lenovo',
            'price'      => 0.01,          // <-- el ataque
            'quantity'   => 1,
        ]]);

        $r->assertSuccessful();

        $order = Order::latest('id')->first();
        $this->assertSame('2799.00', (string) $order->total,
            'el total debe salir del catalogo, no de lo que mando el cliente');
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'price' => '2799.00',
        ]);
        $this->assertDatabaseMissing('order_items', ['price' => '0.01']);
    }

    /** Tampoco vale inflarlo: el catalogo manda en los dos sentidos. */
    public function test_un_precio_mayor_tampoco_se_respeta(): void
    {
        $this->pedir([[
            'product_id' => $this->producto->id, 'name' => 'Laptop Lenovo',
            'price' => 99999.00, 'quantity' => 1,
        ]])->assertSuccessful();

        $this->assertSame('2799.00', (string) Order::latest('id')->first()->total);
    }

    /** Cantidades: el importe se multiplica en centavos exactos. */
    public function test_el_importe_se_calcula_en_centavos_con_el_precio_real(): void
    {
        $this->pedir([[
            'product_id' => $this->producto->id, 'name' => 'Laptop Lenovo',
            'price' => 1.00, 'quantity' => 3,
        ]])->assertSuccessful();

        $this->assertSame('8397.00', (string) Order::latest('id')->first()->total);
    }

    /**
     * Hay pedidos reales cuyo carrito NO trajo `product_id` (id no numerico).
     * Se resuelven por nombre para no romperlos, y el precio sigue saliendo del
     * catalogo.
     */
    public function test_una_linea_sin_product_id_se_resuelve_por_nombre(): void
    {
        $this->pedir([[
            'product_id' => null, 'name' => 'Laptop Lenovo',
            'price' => 5.00, 'quantity' => 1,
        ]])->assertSuccessful();

        $order = Order::latest('id')->first();
        $this->assertSame('2799.00', (string) $order->total);
        // Y de paso queda enlazado al producto, que antes se perdia.
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'product_id' => $this->producto->id,
        ]);
    }

    /** Un producto inventado no se vende: lo que no se puede verificar, no pasa. */
    public function test_un_producto_que_no_existe_se_rechaza(): void
    {
        $r = $this->pedir([[
            'product_id' => 99999, 'name' => 'Producto Fantasma',
            'price' => 1.00, 'quantity' => 1,
        ]]);

        $r->assertStatus(422);
        $this->assertSame(0, Order::count(), 'no puede crearse el pedido');
    }

    /** Un producto de OTRO proyecto tampoco: sin cruce entre negocios. */
    public function test_no_se_puede_comprar_un_producto_de_otro_proyecto(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otra', 'slug' => 'otra-tienda', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajeno = $otro->products()->create([
            'name' => 'Ajeno', 'price' => '10.00', 'stock' => 5, 'is_available' => true,
        ]);

        $this->pedir([[
            'product_id' => $ajeno->id, 'name' => 'Ajeno',
            'price' => 10.00, 'quantity' => 1,
        ]])->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    /** El stock se descuenta por el importe correcto, no por el manipulado. */
    public function test_el_stock_se_descuenta_igual(): void
    {
        $this->pedir([[
            'product_id' => $this->producto->id, 'name' => 'Laptop Lenovo',
            'price' => 0.01, 'quantity' => 2,
        ]])->assertSuccessful();

        $this->assertSame(8, (int) $this->producto->fresh()->stock);
    }

    /** El envío entra al total guardado: no se cobra menos de lo mostrado. */
    public function test_el_envio_se_suma_al_total_del_pedido(): void
    {
        $this->pedir([[
            'product_id' => $this->producto->id, 'name' => 'Laptop Lenovo',
            'price' => 2799.00, 'quantity' => 1,
        ]], ['shipping_cost' => 15.00])->assertSuccessful();

        $order = Order::latest('id')->first();
        $this->assertSame('2814.00', (string) $order->total,
            'el total guardado debe incluir el envío que vio el cliente');
    }
}
