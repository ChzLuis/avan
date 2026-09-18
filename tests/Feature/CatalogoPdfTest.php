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
 * El catálogo PDF con dos niveles de contenido: main (una foto por modelo) y
 * full (cada color desplegado como producto propio, con su tarjeta y su foto
 * en grande). 'variants' se acepta como alias de full por compatibilidad.
 *
 * Lo que más se protege es la regresión silenciosa: una tienda sin colores
 * tiene que generar exactamente el PDF de siempre, y ningún color puede
 * quedarse escondido.
 */
class CatalogoPdfTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Category $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Ropa PDF',
            'slug'      => 'ropa-pdf',
            'is_active' => true,
        ]);
        $this->categoria = Category::create(['project_id' => $this->project->id, 'name' => 'Casacas']);
    }

    /** @param array<string,string> $colorImages */
    private function producto(string $nombre, array $colorImages = []): Product
    {
        $p = Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $this->categoria->id,
            'name'        => $nombre,
            'price'       => 50,
            'options'     => $colorImages ? ['colors' => array_keys($colorImages), 'color_images' => $colorImages] : null,
        ]);

        ProductImage::create([
            'product_id' => $p->id,
            'url'        => "/storage/products/{$p->id}/principal.jpg",
            'is_main'    => true,
            'sort_order' => 0,
        ]);

        return $p;
    }

    /** Renderiza la vista del PDF igual que el controlador. */
    private function pdf(string $content, string $layout = 'grid3'): string
    {
        $products = $this->project->products()
            ->with(['images' => fn ($q) => $q->where('is_main', true), 'category.parent'])
            ->get();

        $groups = $products->groupBy(fn ($p) => $p->category?->name ?? 'Sin categoría');

        return view('catalogo::catalog.products.catalog-pdf', [
            'project'  => $this->project,
            'groups'   => $groups,
            'prices'   => 'retail',
            'profile'  => null,
            'category' => null,
            'settings' => collect(),
            'layout'   => $layout,
            'cover'    => false,
            'storeUrl' => 'https://ejemplo.pe',
            'content'  => $content,
        ])->render();
    }

    // ── Caso 1: sin colores, nada cambia ─────────────────────────────────────

    public function test_un_producto_sin_colores_genera_una_sola_tarjeta_en_todos_los_modos(): void
    {
        $this->producto('Monitor 24');

        foreach (['main', 'variants', 'full'] as $modo) {
            $html = $this->pdf($modo);
            $this->assertSame(1, substr_count($html, 'class="card"'), "Tarjetas de más en {$modo}.");
            $this->assertStringNotContainsString('Monitor 24 —', $html, "Sufijo de color inventado en {$modo}.");
        }
    }

    public function test_un_solo_color_no_se_despliega(): void
    {
        $this->producto('Casaca lisa', ['Rojo' => '/storage/products/1/rojo.jpg']);

        $html = $this->pdf('full');
        $this->assertSame(1, substr_count($html, 'class="card"'));
        $this->assertStringNotContainsString('Casaca lisa —', $html);
    }

    // ── El contrato central: cada color es un producto propio ────────────────

    public function test_cada_color_es_una_tarjeta_propia(): void
    {
        $this->producto('Casaca A', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg', 'Lila' => '/l.jpg']);

        $html = $this->pdf('full');
        $this->assertSame(3, substr_count($html, 'class="card"'), 'Una tarjeta por color.');
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->assertStringContainsString("Casaca A — {$color}", $html);
        }
    }

    public function test_ocho_colores_salen_los_ocho(): void
    {
        $colores = [];
        foreach (range(1, 8) as $i) {
            $colores["Color {$i}"] = "/c{$i}.jpg";
        }
        $this->producto('Casaca B', $colores);

        $html = $this->pdf('full', 'grid2');
        $this->assertSame(8, substr_count($html, 'class="card"'));
        foreach (range(1, 8) as $i) {
            $this->assertStringContainsString("Casaca B — Color {$i}", $html, "Se perdió el color {$i}.");
        }
    }

    public function test_cada_color_usa_su_propia_foto(): void
    {
        $this->producto('Casaca C', ['Rojo' => '/rojo-unico.jpg', 'Azul' => '/azul-unico.jpg']);

        $html = $this->pdf('full');
        $this->assertStringContainsString('rojo-unico', $html);
        $this->assertStringContainsString('azul-unico', $html);
    }

    public function test_cada_color_lleva_su_precio(): void
    {
        $this->producto('Casaca K', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg']);

        $this->assertSame(2, substr_count($this->pdf('full'), 'class="price"'));
    }

    /** 'variants' quedó como alias de full: los enlaces viejos no cambian de significado. */
    public function test_variants_es_alias_de_full(): void
    {
        $this->producto('Casaca D', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg']);

        $this->assertSame($this->pdf('full'), $this->pdf('variants'));
    }

    // ── Modo principal ───────────────────────────────────────────────────────

    public function test_en_modo_principal_solo_hay_una_tarjeta_por_modelo(): void
    {
        $this->producto('Casaca E', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg']);

        $html = $this->pdf('main');
        $this->assertSame(1, substr_count($html, 'class="card"'));
        $this->assertStringNotContainsString('Casaca E —', $html);
    }

    // ── Layouts ──────────────────────────────────────────────────────────────

    public function test_el_despliegue_funciona_en_las_tres_densidades(): void
    {
        $this->producto('Casaca F', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg', 'Lila' => '/l.jpg']);

        foreach (['grid2', 'grid3', 'grid4'] as $layout) {
            $this->assertSame(3, substr_count($this->pdf('full', $layout), 'class="card"'), "Colores perdidos en {$layout}.");
        }
    }

    // ── Robustez ─────────────────────────────────────────────────────────────

    /** Una foto rota se quita sola; el PDF nunca se aborta por ella. */
    public function test_la_foto_de_la_tarjeta_lleva_su_salvavidas_onerror(): void
    {
        $this->producto('Casaca H', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg']);

        $this->assertStringContainsString('onerror=', $this->pdf('full'));
    }

    /** Caso mixto: con y sin colores conviven en el mismo catálogo. */
    public function test_catalogo_mixto(): void
    {
        $this->producto('Casaca I', ['Rojo' => '/r.jpg', 'Azul' => '/a.jpg']);
        $this->producto('Monitor 27');

        $html = $this->pdf('full');
        $this->assertSame(3, substr_count($html, 'class="card"'), '2 colores + 1 sin colores.');
        $this->assertStringContainsString('Casaca I — Rojo', $html);
        $this->assertStringContainsString('Monitor 27', $html);
    }
}
