<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Support\FlowEngine\FlowRunner;
use App\Support\FlowEngine\PlantillaComercial;
use App\Support\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Búsqueda comercial: atributos (G4), alternativas honestas (G5) y la pregunta
 * desambiguadora cuando los resultados se reparten en secciones (G3 nivel 5).
 *
 * Lo que más se protege: el bot NUNCA afirma que encontró lo que le pidieron
 * cuando solo encontró algo parecido.
 */
class BotBusquedaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Tienda Búsqueda',
            'slug'      => 'tienda-busqueda',
            'is_active' => true,
        ]);
    }

    private function categoria(string $nombre): Category
    {
        return Category::create(['project_id' => $this->project->id, 'name' => $nombre]);
    }

    private function producto(string $nombre, float $precio, ?Category $cat = null, ?array $options = null): Product
    {
        return Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $cat?->id,
            'name'        => $nombre,
            'price'       => $precio,
            'options'     => $options,
        ]);
    }

    private function conversar(array $mensajes): string
    {
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];
        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, '51977000333');
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $salida[] = is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    // ── G4: atributos reales (colores y tallas) ──────────────────────────────

    public function test_busca_por_color_guardado_en_options(): void
    {
        $ropa = $this->categoria('Casacas');
        $this->producto('Casaca térmica con forro pima', 60, $ropa, ['colors' => ['Rosado', 'Lila'], 'sizes' => ['2', '4']]);
        $this->producto('Pantalón jean clásico', 45, $ropa);

        $r = ProjectContext::for($this->project)->buscarTolerante('casaca rosado');

        $this->assertSame('Casaca térmica con forro pima', $r->first()['nombre']);
        $this->assertTrue($r->first()['exacto'], 'Nombre + color: coincidencia completa.');
    }

    public function test_busca_por_talla(): void
    {
        $ropa = $this->categoria('Casacas');
        $this->producto('Overol de corduroy', 40, $ropa, ['sizes' => ['2', '4', '6']]);

        // La busqueda tolerante de ProjectContext sigue viva (la usan la capa
        // IA y las recomendaciones): talla por su VALOR.
        $r = ProjectContext::for($this->project)->buscarTolerante('overol 4');
        $this->assertCount(1, $r);
        $this->assertTrue($r->first()['exacto']);

        // El BOT, con el contrato simple, lleva a la seccion.
        $txt = $this->conversar(['hola', 'casacas']);
        $this->assertStringContainsString('*Casacas*', $txt);
    }

    /** La marca por catálogo está vacía en producción; no debe romper nada. */
    public function test_producto_sin_marca_asignada_se_busca_igual(): void
    {
        $this->producto('Taladro percutor 650W', 199);

        $r = ProjectContext::for($this->project)->buscarTolerante('taladro');

        $this->assertCount(1, $r);
    }

    // ── G5: exacto vs alternativa ────────────────────────────────────────────

    public function test_marca_como_alternativa_lo_que_no_coincide_del_todo(): void
    {
        $m = $this->categoria('Monitores');
        $this->producto('Monitor Samsung 24 FHD', 549, $m);
        $this->producto('Monitor Samsung 27 QHD', 899, $m);

        $r = ProjectContext::for($this->project)->buscarTolerante('monitor samsung 32 curvo');

        $this->assertNotEmpty($r);
        $this->assertEmpty($r->filter(fn ($p) => $p['exacto']), 'Ningún resultado cumple TODAS las palabras.');
    }

    public function test_el_bot_no_afirma_haber_encontrado_un_modelo_que_no_existe(): void
    {
        $m = $this->categoria('Monitores');
        $this->producto('Monitor Samsung 24 FHD', 549, $m);

        $txt = $this->conversar(['hola', 'tienen monitor samsung 32 curvo']);

        // Sin alternativas ni "no tengo exactamente": la seccion real.
        $this->assertStringContainsString('*Monitores*', $txt);
        $this->assertStringNotContainsString('No tengo exactamente', $txt);
        $this->assertStringNotContainsString('alternativas', $txt);
    }

    public function test_una_coincidencia_real_sigue_dando_la_ficha_directa(): void
    {
        $m = $this->categoria('Monitores');
        $this->producto('Monitor Samsung 24 FHD', 549, $m);

        $txt = $this->conversar(['hola', 'cuanto cuesta el monitor samsung 24']);

        // Contrato simple: sin fichas en el chat; la seccion con link.
        $this->assertStringContainsString('*Monitores*', $txt);
        $this->assertStringContainsString('/tienda/', $txt);
    }

    /** Elegir una alternativa por número lleva a su ficha real. */
    public function test_se_puede_elegir_una_alternativa_por_numero(): void
    {
        $this->categoria('Monitores');
        $this->categoria('Monitores Gamer');

        $txt = $this->conversar(['hola', 'monitor', '1']);

        $this->assertStringContainsString('/tienda/', $txt, 'Elegir seccion abre su link.');
    }

    // ── G3 nivel 5: pregunta desambiguadora por sección ──────────────────────

    public function test_pregunta_de_que_seccion_cuando_los_resultados_se_reparten(): void
    {
        $this->categoria('Laptops');
        $this->categoria('Laptops Gamer');

        $txt = $this->conversar(['hola', 'laptop']);

        $this->assertStringContainsString('distintas secciones', $txt);
        $this->assertStringContainsString('Laptops', $txt);
        $this->assertStringContainsString('Laptops Gamer', $txt);
    }

    public function test_elegir_la_seccion_acota_la_busqueda(): void
    {
        $this->categoria('Laptops');
        $this->categoria('Laptops Gamer');

        $txt = $this->conversar(['hola', 'laptop', '2']);

        $this->assertStringContainsString('laptops-gamer', $txt, 'El link es de LA seccion elegida.');
    }

    public function test_seccion_fuera_de_rango_no_revienta(): void
    {
        $this->categoria('Laptops');
        $this->categoria('Laptops Gamer');

        $txt = $this->conversar(['hola', 'laptop', '9']);

        $this->assertStringContainsString('no está en la lista', $txt);
    }

    /** Pocos resultados en varias secciones: se listan, no se pregunta. */
    public function test_con_pocos_resultados_no_se_pregunta_la_seccion(): void
    {
        $a = $this->categoria('Laptops');
        $b = $this->categoria('Impresoras');
        $this->producto('HP ProBook 450', 3200, $a);
        $this->producto('HP LaserJet M110', 750, $b);

        $txt = $this->conversar(['hola', 'que tienen de hp']);

        $this->assertStringNotContainsString('distintas secciones', $txt);
    }
}
