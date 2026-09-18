<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Category;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Modules\Tienda\Storefront\AgrupadorModelos;
use App\Modules\Tienda\Storefront\CatalogQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Una tienda de ropa carga cada color como un producto aparte —lo correcto para
 * el stock y la factura—, pero en el escaparate eso llena la rejilla con siete
 * fotos casi iguales. Estas pruebas fijan que la agrupación junte lo que es el
 * mismo modelo y, sobre todo, que NO junte lo que no lo es.
 */
class AgrupacionModelosTest extends TestCase
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
            'slug'      => 'ropa-test',
            'is_active' => true,
        ]);
        $this->categoria = Category::create(['project_id' => $this->project->id, 'name' => 'Casacas']);
    }

    private function producto(string $nombre, ?string $color = null, float $precio = 40, ?Category $cat = null): Product
    {
        return Product::create([
            'project_id'  => $this->project->id,
            'category_id' => ($cat ?? $this->categoria)->id,
            'name'        => $nombre,
            'price'       => $precio,
            'options'     => $color ? ['colors' => [$color], 'sizes' => ['2', '4']] : null,
        ]);
    }

    private function activarAgrupacion(): void
    {
        $this->project->settings()->updateOrCreate(['key' => 'catalog_group_models'], ['value' => '1']);
        $this->project->refresh();
    }

    // ── Qué es el mismo modelo ────────────────────────────────────────────────

    public function test_el_nombre_del_modelo_pierde_la_coletilla_del_color(): void
    {
        $p = $this->producto('Casaca térmica con forro pima - Color Rosado claro', 'Rosado claro');

        $this->assertSame('Casaca térmica con forro pima', AgrupadorModelos::claveModelo($p));
        $this->assertSame('Rosado claro', AgrupadorModelos::colorDe($p));
    }

    /** Sin la palabra "Color" delante, también: el color declarado manda. */
    public function test_reconoce_el_color_aunque_no_lleve_la_palabra_color(): void
    {
        $p = $this->producto('Polo básico - Azul marino', 'Azul marino');

        $this->assertSame('Polo básico', AgrupadorModelos::claveModelo($p));
    }

    /**
     * La trampa que hay que evitar: partir por cualquier guion juntaría tallas,
     * medidas o modelos distintos que no tienen nada que ver.
     */
    public function test_no_recorta_un_guion_que_no_es_el_color(): void
    {
        $p = $this->producto('Polo básico - Talla 4', 'Rojo');

        $this->assertSame('Polo básico - Talla 4', AgrupadorModelos::claveModelo($p));
    }

    public function test_un_producto_sin_color_es_su_propio_modelo(): void
    {
        $p = $this->producto('Mameluco liso');

        $this->assertSame('Mameluco liso', AgrupadorModelos::claveModelo($p));
        $this->assertNull(AgrupadorModelos::colorDe($p));
    }

    /** Dos secciones distintas no son el mismo artículo aunque se llamen igual. */
    public function test_no_junta_modelos_de_categorias_distintas(): void
    {
        $otra = Category::create(['project_id' => $this->project->id, 'name' => 'Ofertas']);
        $this->producto('Casaca térmica - Color Rojo', 'Rojo');
        $this->producto('Casaca térmica - Color Azul', 'Azul', 40, $otra);

        $this->assertCount(2, app(AgrupadorModelos::class)->representantes($this->project));
    }

    // ── Agrupación ────────────────────────────────────────────────────────────

    public function test_seis_colores_se_convierten_en_una_sola_tarjeta(): void
    {
        foreach (['Rojo', 'Azul', 'Lila', 'Crema', 'Gris', 'Camel'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }

        $representantes = app(AgrupadorModelos::class)->representantes($this->project);

        $this->assertCount(1, $representantes);
    }

    public function test_las_variantes_traen_todos_los_colores_ordenados(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }

        $agrupador = app(AgrupadorModelos::class);
        $id = $agrupador->representantes($this->project)[0];
        $variantes = $agrupador->variantesDe($this->project, $id, $this->project->slug);

        $this->assertCount(3, $variantes);
        $this->assertSame(['Azul', 'Lila', 'Rojo'], array_column($variantes, 'color'));
    }

    /** Un producto único no necesita selector: una opción no es una elección. */
    public function test_un_producto_solo_no_lleva_selector_de_color(): void
    {
        $p = $this->producto('Mameluco liso');

        $this->assertSame([], app(AgrupadorModelos::class)
            ->variantesDe($this->project, $p->id, $this->project->slug));
    }

    // ── En el catálogo ────────────────────────────────────────────────────────

    public function test_apagada_la_agrupacion_el_catalogo_los_lista_uno_a_uno(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }

        $this->assertSame(3, app(CatalogQueryService::class)
            ->paginate($this->project, Request::create('/tienda'))->total());
    }

    public function test_encendida_la_agrupacion_el_catalogo_muestra_un_modelo(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }
        $this->producto('Mameluco liso');
        $this->activarAgrupacion();

        $this->assertSame(2, app(CatalogQueryService::class)
            ->paginate($this->project, Request::create('/tienda'))->total());
    }

    /** Quien escribe "rosado" quiere ver el rosado, no el modelo en azul. */
    public function test_al_buscar_no_se_agrupa(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }
        $this->activarAgrupacion();

        $this->assertSame(3, app(CatalogQueryService::class)
            ->paginate($this->project, Request::create('/tienda?q=Casaca'))->total());
    }

    public function test_la_tarjeta_agrupada_habla_del_modelo_y_lleva_sus_colores(): void
    {
        foreach (['Rojo', 'Azul', 'Lila'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }
        $this->activarAgrupacion();

        $servicio = app(CatalogQueryService::class);
        $producto = $servicio->paginate($this->project, Request::create('/tienda'))->first();
        $card = $servicio->toCard($producto, $this->project->slug);

        $this->assertSame('Casaca térmica', $card['name'], 'La tarjeta es del modelo, no del color.');
        $this->assertCount(3, $card['variantes']);
        $this->assertNotEmpty($card['variantes'][0]['url']);
    }

    // ── Catálogo ya fusionado ─────────────────────────────────────────────────

    /**
     * Tras fusionar, los colores viven dentro del propio producto. La tarjeta
     * tiene que seguir ofreciéndolos: si no, la fusión se llevaría por delante
     * el selector de color de la tienda.
     */
    public function test_un_producto_fusionado_ofrece_sus_propios_colores(): void
    {
        $p = Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $this->categoria->id,
            'name'        => 'Casaca térmica',
            'price'       => 60,
            'options'     => [
                'sizes' => ['2', '4'],
                'colors' => ['Azul', 'Rojo'],
                'color_images' => ['Rojo' => '/storage/a.jpg', 'Azul' => '/storage/b.jpg'],
            ],
        ]);

        $variantes = app(AgrupadorModelos::class)
            ->variantesDe($this->project, $p->id, $this->project->slug);

        $this->assertSame(['Azul', 'Rojo'], array_column($variantes, 'color'));
        $this->assertSame([$p->id, $p->id], array_column($variantes, 'id'), 'Es un solo producto: el id no cambia.');
    }

    /**
     * El selector se dibuja con un bucle indexado por color. Si dos variantes
     * compartieran color, Alpine las tomaría por duplicadas y pintaría una
     * sola: es justo lo que pasaba cuando el bucle iba indexado por id, porque
     * tras fusionar todos los colores son el mismo producto.
     */
    public function test_los_colores_no_se_repiten_dentro_de_un_modelo(): void
    {
        $p = Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $this->categoria->id,
            'name'        => 'Casaca térmica',
            'price'       => 60,
            'options'     => ['color_images' => [
                'Rojo' => '/storage/a.jpg', 'Azul' => '/storage/b.jpg', 'Lila' => '/storage/c.jpg',
            ]],
        ]);

        $variantes = app(AgrupadorModelos::class)
            ->variantesDe($this->project, $p->id, $this->project->slug);

        $colores = array_column($variantes, 'color');
        $this->assertSame($colores, array_unique($colores), 'Cada color aparece una sola vez.');
        $this->assertCount(3, $colores);
    }

    /** Con un solo id no se puede saber qué color se ve: manda la foto. */
    public function test_el_color_activo_se_deduce_de_la_foto_que_se_esta_viendo(): void
    {
        $variantes = [
            ['color' => 'Azul', 'image' => '/storage/b.jpg'],
            ['color' => 'Rojo', 'image' => '/storage/a.jpg'],
        ];

        $this->assertSame('Rojo', AgrupadorModelos::colorActivo($variantes, '/storage/a.jpg'));
        $this->assertSame('Azul', AgrupadorModelos::colorActivo($variantes, null), 'Sin pista, el primero.');

        // La tarjeta pide la foto en .webp y la variante la trae en .jpg: es la
        // misma foto y tiene que reconocerse igual.
        $this->assertSame('Rojo', AgrupadorModelos::colorActivo($variantes, 'https://tienda.pe/storage/a.webp'));
    }

    public function test_un_producto_fusionado_de_un_solo_color_no_lleva_selector(): void
    {
        $p = Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $this->categoria->id,
            'name'        => 'Casaca lisa',
            'price'       => 60,
            'options'     => ['color_images' => ['Rojo' => '/storage/a.jpg']],
        ]);

        $this->assertSame([], app(AgrupadorModelos::class)
            ->variantesDe($this->project, $p->id, $this->project->slug));
    }

    public function test_sin_agrupacion_la_tarjeta_no_trae_variantes(): void
    {
        foreach (['Rojo', 'Azul'] as $color) {
            $this->producto("Casaca térmica - Color {$color}", $color);
        }

        $servicio = app(CatalogQueryService::class);
        $producto = $servicio->paginate($this->project, Request::create('/tienda'))->first();
        $card = $servicio->toCard($producto, $this->project->slug);

        $this->assertSame([], $card['variantes']);
        $this->assertStringContainsString('Color', $card['name']);
    }
}
