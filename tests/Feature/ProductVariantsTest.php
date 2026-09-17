<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Project;
use App\Models\User;
use App\Storefront\CatalogQueryService;
use App\Storefront\ProductVariantMatrixService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda con variantes',
            'slug' => 'tienda-variantes',
            'category' => 'retail',
            'is_active' => true,
        ]);
        $this->product = $this->project->products()->create([
            'name' => 'Polo básico',
            'price' => '50.00',
            'stock' => 20,
            'is_available' => true,
        ]);
    }

    public function test_guarda_una_matriz_y_conserva_precio_stock_y_atributos(): void
    {
        $result = $this->matrixService()->save($this->project, $this->product, $this->payload());

        $this->assertCount(2, $result['attributes']);
        $this->assertCount(4, $result['variants']);
        $this->assertDatabaseCount('product_variants', 4);
        $this->assertDatabaseHas('product_variants', [
            'project_id' => $this->project->id,
            'product_id' => $this->product->id,
            'sku' => 'POLO-AZ-S',
            'price' => '55.00',
            'stock' => 3,
            'is_active' => 1,
        ]);
        $this->assertSame(4, $this->product->fresh()->attributeValues()->count());
    }

    public function test_no_permite_reutilizar_un_atributo_de_otra_tienda(): void
    {
        $other = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otra tienda',
            'slug' => 'otra-variantes',
            'is_active' => true,
        ]);
        $foreign = ProductAttribute::allProjects()->create([
            'project_id' => $other->id,
            'name' => 'Material',
            'slug' => 'material',
            'type' => 'button',
            'is_variant' => true,
            'is_filterable' => true,
            'is_active' => true,
        ]);

        $payload = $this->payload();
        $payload['attributes'][0]['id'] = $foreign->id;

        $this->expectException(ModelNotFoundException::class);
        $this->matrixService()->save($this->project, $this->product, $payload);
    }

    public function test_el_catalogo_filtra_por_atributo_sin_cruzar_proyectos(): void
    {
        $this->matrixService()->save($this->project, $this->product, $this->payload());
        $otherProduct = $this->project->products()->create([
            'name' => 'Polo sin variantes',
            'price' => '40.00',
            'stock' => 10,
            'is_available' => true,
        ]);

        $blue = $this->product->fresh()->attributeValues()
            ->where('label', 'Azul')->firstOrFail();
        $request = Request::create('/tienda', 'GET', [
            'attribute' => [(string) $blue->product_attribute_id => [$blue->id]],
        ]);

        $page = app(CatalogQueryService::class)->paginate($this->project, $request);

        $this->assertSame([$this->product->id], collect($page->items())->pluck('id')->all());
        $this->assertNotContains($otherProduct->id, collect($page->items())->pluck('id')->all());
    }

    public function test_checkout_usa_precio_y_stock_de_la_variante_no_del_navegador(): void
    {
        $matrix = $this->matrixService()->save($this->project, $this->product, $this->payload());
        $variant = collect($matrix['variants'])->firstWhere('sku', 'POLO-AZ-S');

        $response = $this->postJson('/'.$this->project->slug.'/order', [
            'client_name' => 'Cliente QA',
            'client_phone' => '987654321',
            'items' => [[
                'product_id' => $this->product->id,
                'product_variant_id' => $variant['id'],
                'name' => 'Nombre manipulado',
                'price' => 0.01,
                'quantity' => 2,
            ]],
        ]);

        $response->assertSuccessful();
        $order = Order::latest('id')->firstOrFail();
        $this->assertSame('110.00', (string) $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $variant['id'],
            'price' => '55.00',
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant['id'], 'stock' => 1]);
        $this->assertSame(20, (int) $this->product->fresh()->stock);
    }

    public function test_checkout_rechaza_variante_ajena_al_producto(): void
    {
        $matrix = $this->matrixService()->save($this->project, $this->product, $this->payload());
        $variant = collect($matrix['variants'])->first();
        $otherProduct = $this->project->products()->create([
            'name' => 'Producto distinto',
            'price' => '20.00',
            'stock' => 5,
            'is_available' => true,
        ]);

        $this->postJson('/'.$this->project->slug.'/order', [
            'client_name' => 'Cliente QA',
            'client_phone' => '987654321',
            'items' => [[
                'product_id' => $otherProduct->id,
                'product_variant_id' => $variant['id'],
                'quantity' => 1,
            ]],
        ])->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_exige_combinacion_si_el_producto_tiene_variantes(): void
    {
        $this->matrixService()->save($this->project, $this->product, $this->payload());

        $this->postJson('/'.$this->project->slug.'/order', [
            'client_name' => 'Cliente QA',
            'client_phone' => '987654321',
            'items' => [[
                'product_id' => $this->product->id,
                'price' => 50,
                'quantity' => 1,
            ]],
        ])->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertSame(0, Order::count());
    }

    private function matrixService(): ProductVariantMatrixService
    {
        return app(ProductVariantMatrixService::class);
    }

    private function payload(): array
    {
        return [
            'attributes' => [
                [
                    'key' => 'color',
                    'name' => 'Color',
                    'type' => 'color',
                    'is_variant' => true,
                    'is_filterable' => true,
                    'values' => [
                        ['key' => 'blue', 'label' => 'Azul', 'color_hex' => '#2563EB'],
                        ['key' => 'red', 'label' => 'Rojo', 'color_hex' => '#DC2626'],
                    ],
                ],
                [
                    'key' => 'size',
                    'name' => 'Talla',
                    'type' => 'button',
                    'is_variant' => true,
                    'is_filterable' => true,
                    'values' => [
                        ['key' => 'small', 'label' => 'S'],
                        ['key' => 'medium', 'label' => 'M'],
                    ],
                ],
            ],
            'variants' => [
                ['value_keys' => ['blue', 'small'], 'sku' => 'POLO-AZ-S', 'price' => 55, 'stock' => 3, 'is_active' => true],
                ['value_keys' => ['blue', 'medium'], 'sku' => 'POLO-AZ-M', 'price' => 56, 'stock' => 4, 'is_active' => true],
                ['value_keys' => ['red', 'small'], 'sku' => 'POLO-RJ-S', 'price' => 57, 'stock' => 5, 'is_active' => true],
                ['value_keys' => ['red', 'medium'], 'sku' => 'POLO-RJ-M', 'price' => 58, 'stock' => 6, 'is_active' => true],
            ],
        ];
    }
}
