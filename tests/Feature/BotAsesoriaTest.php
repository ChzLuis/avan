<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\StoreSection;
use App\Models\User;
use App\Support\FlowEngine\FlowRunner;
use App\Support\FlowEngine\PlantillaComercial;
use App\Support\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Asesoría comercial: preguntas frecuentes (G6), recomendaciones con
 * presupuesto (G7) y comparaciones entre productos (G8).
 *
 * La regla que se protege en los tres casos es la misma de siempre: cada dato
 * que sale por el chat existe antes en la base. Si no existe, el bot lo dice.
 */
class BotAsesoriaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Tienda Asesoría',
            'slug'      => 'tienda-asesoria',
            'is_active' => true,
        ]);
    }

    private function faqDeLaTienda(array $items): void
    {
        StoreSection::create([
            'project_id' => $this->project->id,
            'page'       => 'home',
            'component'  => 'faq',
            'is_enabled' => true,
            'content'    => ['items' => $items],
        ]);
    }

    private function producto(string $nombre, float $precio, ?Category $cat = null): Product
    {
        return Product::create([
            'project_id'  => $this->project->id,
            'category_id' => $cat?->id,
            'name'        => $nombre,
            'price'       => $precio,
            'stock'       => 5,
        ]);
    }

    private function conversar(array $mensajes): string
    {
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];
        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, '51977000444');
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $salida[] = is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    // ── G6: preguntas frecuentes ─────────────────────────────────────────────

    public function test_responde_con_la_faq_que_escribio_el_dueno(): void
    {
        $this->faqDeLaTienda([
            ['question' => '¿Cuánto demora el envío?', 'answer' => 'Entre 24 y 48 horas en Lima.', 'enabled' => true, 'sort_order' => 0],
            ['question' => '¿Tienen garantía?', 'answer' => 'Sí, 12 meses por defecto de fábrica.', 'enabled' => true, 'sort_order' => 1],
        ]);

        $txt = $this->conversar(['hola', 'cuanto demora el envio']);

        $this->assertStringContainsString('24 y 48 horas', $txt);
    }

    public function test_la_respuesta_es_literal_no_una_parafrasis(): void
    {
        $this->faqDeLaTienda([
            ['question' => '¿Tienen garantía?', 'answer' => 'Sí, 12 meses por defecto de fábrica.', 'enabled' => true],
        ]);

        $item = ProjectContext::for($this->project)->faqQueResponde('garantia');

        $this->assertSame('Sí, 12 meses por defecto de fábrica.', $item['respuesta']);
    }

    public function test_sin_faq_registrada_lo_dice_en_vez_de_inventar(): void
    {
        $txt = $this->conversar(['hola', 'tienen garantia']);

        $this->assertStringContainsString('asesor', $txt);
        $this->assertStringNotContainsString('meses', $txt, 'Sin datos no se improvisa una política.');
    }

    public function test_una_faq_deshabilitada_no_se_responde(): void
    {
        $this->faqDeLaTienda([
            ['question' => '¿Tienen garantía?', 'answer' => 'Sí, 12 meses.', 'enabled' => false],
        ]);

        $this->assertNull(ProjectContext::for($this->project)->faqQueResponde('garantia'));
    }

    public function test_si_ninguna_faq_encaja_ofrece_las_que_existen(): void
    {
        $this->faqDeLaTienda([
            ['question' => '¿Cuánto demora el envío?', 'answer' => 'Entre 24 y 48 horas.', 'enabled' => true, 'sort_order' => 0],
            ['question' => '¿Tienen garantía?', 'answer' => 'Sí, 12 meses.', 'enabled' => true, 'sort_order' => 1],
        ]);

        // Se elige por número una de las ofrecidas y llega su respuesta real.
        $txt = $this->conversar(['hola', 'aceptan devoluciones de productos', '2']);

        $this->assertStringContainsString('No tengo esa pregunta registrada', $txt);
        $this->assertStringContainsString('12 meses', $txt);
    }

    /** La FAQ es del proyecto: nunca se cruza con la de otra empresa. */
    public function test_la_faq_no_se_filtra_entre_proyectos(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otra', 'slug' => 'otra', 'is_active' => true,
        ]);
        StoreSection::create([
            'project_id' => $otro->id, 'page' => 'home', 'component' => 'faq', 'is_enabled' => true,
            'content' => ['items' => [['question' => '¿Tienen garantía?', 'answer' => 'Secreto ajeno.', 'enabled' => true]]],
        ]);

        $this->assertSame([], ProjectContext::for($this->project)->faq());
    }

    // ── G7: recomendación con presupuesto ────────────────────────────────────

    public function test_recomienda_solo_productos_dentro_del_presupuesto(): void
    {
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $this->producto('Laptop Lenovo IdeaPad 3', 1899, $c);
        $this->producto('Laptop HP Victus 15', 2450, $c);
        $this->producto('Laptop Dell XPS 13', 5200, $c);

        $txt = $this->conversar(['hola', 'que laptop me recomiendas hasta 2500 soles']);

        $this->assertStringContainsString('Lenovo IdeaPad 3', $txt);
        $this->assertStringContainsString('HP Victus 15', $txt);
        $this->assertStringNotContainsString('Dell XPS 13', $txt, 'Se pasa del tope declarado.');
    }

    public function test_sin_nada_en_presupuesto_lo_dice_y_ofrece_asesor(): void
    {
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $this->producto('Laptop Dell XPS 13', 5200, $c);

        $txt = $this->conversar(['hola', 'recomiendame una laptop hasta 800 soles']);

        $this->assertStringContainsString('No tengo productos', $txt);
        $this->assertStringNotContainsString('Dell XPS 13', $txt);
    }

    public function test_el_tope_de_precio_sale_del_mensaje_sin_ia(): void
    {
        Http::fake();

        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $this->producto('Laptop Lenovo IdeaPad 3', 1899, $c);

        $txt = $this->conversar(['hola', 'busco una laptop con presupuesto de 2,000']);

        $this->assertStringContainsString('Lenovo IdeaPad 3', $txt);
        Http::assertNothingSent();
    }

    /** El presupuesto sale del texto, no del catálogo: no debe ensuciar la búsqueda. */
    public function test_la_cifra_del_presupuesto_no_se_busca_como_producto(): void
    {
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $m = Category::create(['project_id' => $this->project->id, 'name' => 'Monitores']);
        $this->producto('Laptop Lenovo IdeaPad 3', 1899, $c);
        $this->producto('Monitor Samsung 24', 549, $m);

        $txt = $this->conversar(['hola', 'recomiendame una laptop hasta 2500']);

        $this->assertStringContainsString('Lenovo IdeaPad 3', $txt);
        $this->assertStringNotContainsString('Monitor Samsung', $txt);
    }

    public function test_se_puede_pedir_el_detalle_de_una_recomendacion(): void
    {
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $this->producto('Laptop Lenovo IdeaPad 3', 1899, $c);
        $this->producto('Laptop HP Victus 15', 2450, $c);

        $txt = $this->conversar(['hola', 'que laptop me recomiendas hasta 2500', '1']);

        $this->assertMatchesRegularExpression('/1,?899\.00|2,?450\.00/', $txt);
    }

    // ── G8: comparación entre dos productos ──────────────────────────────────

    public function test_compara_dos_productos_de_la_lista_con_datos_reales(): void
    {
        // Contrato simple: la comparacion detallada vive en la web; el bot
        // lleva a la seccion correspondiente.
        Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        Category::create(['project_id' => $this->project->id, 'name' => 'Laptops Gamer']);

        $txt = $this->conversar(['hola', 'que laptops tienen', '1']);

        $this->assertStringContainsString('/tienda/', $txt);
        $this->assertStringContainsString('Catálogo PDF', $txt);
    }

    public function test_comparar_numeros_fuera_de_la_lista_no_revienta(): void
    {
        Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        Category::create(['project_id' => $this->project->id, 'name' => 'Laptops Gamer']);

        $txt = $this->conversar(['hola', 'que laptops tienen', '9']);

        $this->assertStringContainsString('no está en la lista', $txt);
    }

    /**
     * Comparar mientras se pregunta la sección: los números de esa lista son
     * categorías, no productos. Se encamina en vez de buscar la frase entera.
     */
    public function test_comparar_mientras_se_elige_seccion_pide_elegir_primero(): void
    {
        $a = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $b = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops Gamer']);
        $this->producto('Laptop Lenovo IdeaPad 3', 1899, $a);
        $this->producto('Laptop HP Victus 15', 2450, $a);
        $this->producto('Laptop ASUS ROG Strix', 6200, $b);
        $this->producto('Laptop MSI Katana 15', 4800, $b);

        $txt = $this->conversar(['hola', 'que laptops tienen', 'cual es la diferencia entre el 1 y el 2']);

        $this->assertStringContainsString('primero elige la sección', $txt);
        $this->assertStringNotContainsString('No tengo exactamente', $txt, 'La frase no se busca como producto.');
    }

    /**
     * Una pregunta de FAQ hecha a mitad de una consulta de productos se
     * responde con la FAQ, no con "alternativas" absurdas del catálogo
     * (pasó de verdad: "hacen entrega e instalación" ofreció bicicletas).
     */
    public function test_una_faq_en_medio_de_la_consulta_gana_a_las_alternativas(): void
    {
        $this->faqDeLaTienda([
            ['question' => '¿Hacen entrega e instalación?', 'answer' => 'Sí, en Lima la entrega e instalación son gratis.', 'enabled' => true],
        ]);
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);
        $this->producto('Cocina a gas 4 hornillas', 490, $c);
        $this->producto('Bicicleta montañera aro 29', 850, $c);

        $txt = $this->conversar(['hola', 'tienen cocina a gas', 'hacen entrega e instalacion']);

        $this->assertStringContainsString('instalación son gratis', $txt);
        $this->assertStringNotContainsString('Bicicleta', $txt);
        $this->assertStringNotContainsString('No tengo exactamente *hacen entrega', $txt);
    }

    /**
     * "a que zonas llegan" contiene la palabra del comando "zonas", pero es
     * una pregunta de cobertura: debe responder la FAQ, no tragarse la frase
     * como comando y pedir el nombre de un producto (fallo real en el E2E).
     */
    public function test_una_pregunta_con_palabra_de_comando_no_se_traga_como_comando(): void
    {
        $this->faqDeLaTienda([
            ['question' => '¿A qué zonas llegan?', 'answer' => 'Llegamos a toda la provincia de Huancavelica.', 'enabled' => true],
        ]);
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);
        $this->producto('Cocina a gas 4 hornillas', 490, $c);

        // A mitad de una consulta de productos, como pasó de verdad.
        $txt = $this->conversar(['hola', 'tienen cocina a gas', 'a que zonas llegan']);

        $this->assertStringContainsString('provincia de Huancavelica', $txt);
        $this->assertStringNotContainsString('Escríbeme el *nombre del producto*', $txt);
    }

    /** Y una búsqueda genuina de producto NO se desvía a la FAQ. */
    public function test_una_busqueda_de_producto_no_se_desvia_a_la_faq(): void
    {
        Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);

        $txt = $this->conversar(['hola', 'tienen cocina a gas']);

        $this->assertStringContainsString('*Cocinas*', $txt, 'Va a la sección, no a la FAQ.');
        $this->assertStringNotContainsString('gratis en Lima', $txt);
    }

    // ── Un producto sin precio no vale cero ──────────────────────────────────

    /**
     * Publicar un producto sin precio es normal (aún no se define). Lo que no
     * puede pasar es que el bot lo anuncie como "S/ 0.00": eso es dar un precio
     * falso, y el cliente lo leería como gratis.
     */
    public function test_un_producto_sin_precio_no_se_anuncia_como_cero(): void
    {
        Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);

        $txt = $this->conversar(['hola', 'cuanto cuesta la cocina mabe']);

        $this->assertStringNotContainsString('S/ 0.00', $txt, 'Jamás un precio falso.');
        $this->assertStringContainsString('*Cocinas*', $txt, 'Lleva a la sección con datos reales.');
    }

    public function test_la_ficha_de_un_producto_sin_precio_deriva_a_asesor(): void
    {
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);
        $this->producto('Cocina Mabe 4 hornillas premium', 0, $c);

        $txt = $this->conversar(['hola', 'cuanto cuesta la cocina mabe premium']);

        $this->assertStringNotContainsString('S/ 0.00', $txt);
        $this->assertStringContainsString('asesor', $txt);
    }

    public function test_no_se_calcula_diferencia_contra_un_producto_sin_precio(): void
    {
        $txt = $this->conversar(['hola', 'que cocinas tienen', 'compara el 1 y el 2']);

        $this->assertStringNotContainsString('820.00 menos', $txt, 'No se inventa un ahorro contra cero.');
        $this->assertStringNotContainsString('S/ 0.00', $txt);
    }

    /** Y nunca se recomienda por presupuesto algo cuyo precio no se conoce. */
    public function test_la_recomendacion_ignora_productos_sin_precio(): void
    {
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);
        $this->producto('Cocina Mabe 4 hornillas', 820, $c);
        $this->producto('Cocina Mabe 4 hornillas premium', 0, $c);

        $txt = $this->conversar(['hola', 'que cocina me recomiendas hasta 1000']);

        $this->assertStringContainsString('Cocina Mabe 4 hornillas', $txt);
        $this->assertStringNotContainsString('premium', $txt);
    }

    /** Nada de esto consume IA en un proyecto sin licencia. */
    public function test_faq_recomendacion_y_comparacion_no_llaman_al_proveedor(): void
    {
        Http::fake();
        $this->faqDeLaTienda([['question' => '¿Cuánto demora el envío?', 'answer' => '24 horas.', 'enabled' => true]]);
        $c = Category::create(['project_id' => $this->project->id, 'name' => 'Laptops']);
        $this->producto('Laptop Lenovo IdeaPad 3', 1899, $c);
        $this->producto('Laptop HP Victus 15', 2450, $c);

        $this->conversar(['hola', 'cuanto demora el envio']);
        $this->conversar(['hola', 'recomiendame una laptop hasta 2500']);
        $this->conversar(['hola', 'que laptops tienen', 'diferencia entre el 1 y el 2']);

        Http::assertNothingSent();
    }
}
