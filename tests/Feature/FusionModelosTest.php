<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Category;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Catalogo\Models\ProductImage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `productos:fusionar-modelos` borra productos, así que lo que se protege aquí
 * es sobre todo lo que NO debe pasar: perder una foto, perder una talla o
 * borrar un producto que ya se vendió.
 */
class FusionModelosTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Category $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Ropa',
            'slug'      => 'ropa-fusion',
            'is_active' => true,
        ]);
        $this->categoria = Category::create(['project_id' => $this->project->id, 'name' => 'Casacas']);
    }

    private function color(string $color, array $tallas = ['2', '4'], string $desc = ''): Product
    {
        $p = Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $this->categoria->id,
            'name'        => "Casaca térmica - Color {$color}",
            'price'       => 60,
            'description' => $desc ?: "Casaca térmica. Color: {$color}. Tallas: 2 y 4.",
            'options'     => ['colors' => [$color], 'sizes' => $tallas],
        ]);

        ProductImage::create([
            'product_id' => $p->id,
            'url'        => "/storage/products/{$p->id}/{$color}.jpg",
            'is_main'    => true,
            'sort_order' => 0,
        ]);

        return $p;
    }

    private function fusionar(array $opciones = []): void
    {
        $this->artisan('productos:fusionar-modelos', ['proyecto' => $this->project->id] + $opciones);
    }

    // ── Lo que hace ───────────────────────────────────────────────────────────

    public function test_tres_colores_quedan_en_un_solo_producto(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->color($color);
        }

        $this->fusionar();

        $this->assertSame(1, Product::where('project_id', $this->project->id)->count());
        $this->assertSame('Casaca térmica', Product::where('project_id', $this->project->id)->value('name'));
    }

    public function test_el_producto_final_guarda_todos_los_colores(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->color($color);
        }

        $this->fusionar();
        $p = Product::where('project_id', $this->project->id)->first();

        $this->assertSame(['Azul', 'Lila', 'Rojo'], $p->options['colors']);
        $this->assertCount(3, $p->options['color_images'], 'Cada color conserva su foto.');
    }

    /** Perder una foto en una fusión es irreversible: van todas a la galería. */
    public function test_no_se_pierde_ninguna_foto(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->color($color);
        }

        $this->fusionar();
        $p = Product::where('project_id', $this->project->id)->first();

        $this->assertSame(3, $p->images()->count());
        $this->assertSame(1, $p->images()->where('is_main', true)->count(), 'Solo una principal.');
    }

    public function test_las_tallas_se_juntan_sin_repetirse(): void
    {
        $this->color('Rojo', ['2', '4']);
        $this->color('Azul', ['4', '6']);

        $this->fusionar();
        $p = Product::where('project_id', $this->project->id)->first();

        $this->assertSame(['2', '4', '6'], array_values($p->options['sizes']));
    }

    /** La descripción decía "Color: Rojo"; fusionada eso ya no es cierto. */
    public function test_la_descripcion_pasa_a_nombrar_todos_los_colores(): void
    {
        $this->color('Rojo');
        $this->color('Azul');

        $this->fusionar();
        $p = Product::where('project_id', $this->project->id)->first();

        $this->assertStringContainsString('Colores: Azul, Rojo.', $p->description);
        $this->assertStringNotContainsString('Color: Rojo.', $p->description);
    }

    // ── Lo que NO hace ────────────────────────────────────────────────────────

    public function test_simular_no_toca_nada(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->color($color);
        }

        $this->fusionar(['--simular' => true]);

        $this->assertSame(3, Product::where('project_id', $this->project->id)->count());
    }

    public function test_no_borra_un_color_que_ya_se_vendio(): void
    {
        $rojo = $this->color('Rojo');
        $azul = $this->color('Azul');

        // Un pedido apuntando al azul: su id vive en el historial.
        $pedido = \DB::table('orders')->insertGetId([
            'project_id' => $this->project->id, 'client_name' => 'Cliente',
            'status' => 'pending', 'total' => 60,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('order_items')->insert([
            'order_id' => $pedido, 'product_id' => $azul->id, 'name' => $azul->name,
            'quantity' => 1, 'price' => 60,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->fusionar();

        $this->assertSame(2, Product::where('project_id', $this->project->id)->count());
        $this->assertNotNull(Product::find($azul->id));
        $this->assertNotNull(Product::find($rojo->id));
    }

    public function test_un_modelo_de_un_solo_color_se_queda_como_esta(): void
    {
        $p = $this->color('Rojo');

        $this->fusionar();

        $this->assertSame(1, Product::where('project_id', $this->project->id)->count());
        $this->assertSame($p->id, Product::where('project_id', $this->project->id)->value('id'));
        $this->assertStringContainsString('Color Rojo', Product::find($p->id)->name);
    }
}
