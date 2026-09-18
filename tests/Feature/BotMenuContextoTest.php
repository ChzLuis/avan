<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Modules\Tienda\Models\StoreSection;
use App\Models\User;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use App\Modules\Bots\Support\FlowEngine\PlantillaComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CONTRATO SIMPLE de búsqueda (2026-08-29):
 *   texto → categorías afines → (varias: elegir sección) → título + link
 *   filtrado + PDF. FIN.
 * Sin productos individuales, sin páginas, sin alternativas, y cada texto
 * nuevo DESCARTA el contexto anterior (el bug real: "Mesa" reutilizaba
 * Camarotes).
 */
class BotMenuContextoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'MegaHogar Test', 'slug' => 'megahogar-test',
            'is_active' => true, 'address' => 'Jr. Odonovan 174',
        ]);

        foreach (['Camas', 'Camarotes', 'Dormitorios', 'Mesas de Centro', 'Mesas de Comedor', 'Televisores', 'Muebles'] as $nom) {
            $cat = Category::create(['project_id' => $this->project->id, 'name' => $nom]);
            Product::create(['project_id' => $this->project->id, 'category_id' => $cat->id,
                'name' => 'Producto de ' . $nom, 'price' => 100, 'stock' => 3]);
        }

        StoreSection::create([
            'project_id' => $this->project->id, 'page' => 'home', 'component' => 'faq', 'is_enabled' => true,
            'content' => ['items' => [
                ['question' => '¿Hacen entrega e instalación?', 'answer' => 'Sí, coordinamos la entrega e instalación del mueble.', 'enabled' => true],
            ]],
        ]);
    }

    private function conversar(array $mensajes, ?array &$estado = null): string
    {
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = $estado ?? ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];
        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, '51977000777');
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $salida[] = is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    /** Nada del flujo viejo puede aparecer, jamás. */
    private function sinFlujoViejo(string $txt): void
    {
        foreach (['resultados 1–', 'Ver siguientes', 'Ver más', 'ver más', 'No tengo exactamente', 'alternativas'] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $txt, "Apareció flujo viejo: «{$prohibido}»");
        }
    }

    // ═══ ESCENARIO A: varias secciones → elegir → link + PDF. FIN. ══════════

    public function test_a_cama_muestra_secciones_no_productos(): void
    {
        $txt = $this->conversar(['hola', '1', 'cama']);

        $this->assertStringContainsString('distintas secciones', $txt);
        $this->assertStringContainsString('Camas', $txt);
        $this->assertStringContainsString('Camarotes', $txt);
        $this->assertStringNotContainsString('Producto de', $txt, 'NO se muestran productos.');
        $this->sinFlujoViejo($txt);
    }

    public function test_a_elegir_camarotes_da_link_y_pdf_y_fin(): void
    {
        $estado = null;
        $this->conversar(['hola', '1', 'cama'], $estado);

        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $idx = array_search(
            Category::where('project_id', $this->project->id)->where('name', 'Camarotes')->value('id'),
            $estado['vars']['_categorias']
        ) + 1;
        $res = $runner->procesar((string) $idx, $estado, '51977000777');

        $textos = collect($res['respuestas']);
        $plano = $textos->map(fn ($r) => is_array($r) ? ($r['fallback'] ?? '') : $r)->implode(' ');
        $archivo = $textos->first(fn ($r) => is_array($r) && ($r['tipo'] ?? '') === 'archivo');

        $this->assertStringContainsString('*Camarotes*', $plano);
        $this->assertStringContainsString('/tienda/camarotes', $plano, 'Link filtrado de la categoría.');
        $this->assertNotNull($archivo, 'Llega el PDF de la categoría.');
        $this->assertStringContainsString('camarotes', $archivo['nombre']);
        $this->assertStringContainsString('signature=', $archivo['url']);
        $this->assertStringNotContainsString('Producto de', $plano, 'Sin productos. FIN.');
        $this->sinFlujoViejo($plano);
    }

    // ═══ ESCENARIO B: texto nuevo = búsqueda NUEVA (el bug real) ════════════

    public function test_b_mesa_despues_de_camarotes_no_reutiliza_camarotes(): void
    {
        $estado = null;
        $txt = $this->conversar(['hola', '1', 'camarote'], $estado);
        $this->assertStringContainsString('*Camarotes*', $txt);

        $txt = $this->conversar(['mesa'], $estado);

        $this->assertStringNotContainsString('Camarotes', $txt, 'FUGA: reutilizó la categoría anterior.');
        $this->assertStringContainsString('Mesas de Centro', $txt);
        $this->assertStringContainsString('Mesas de Comedor', $txt);
        $this->sinFlujoViejo($txt);
    }

    // ═══ ESCENARIO C: una sola categoría → directo, sin preguntar ═══════════

    public function test_c_televisores_va_directo_a_link_y_pdf(): void
    {
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        foreach (['hola', '1'] as $m) { $r = $runner->procesar($m, $estado, '51977000778'); $estado = $r['estado']; }
        $res = $runner->procesar('Televisores', $estado, '51977000778');

        $textos = collect($res['respuestas']);
        $plano = $textos->map(fn ($r) => is_array($r) ? ($r['fallback'] ?? '') : $r)->implode(' ');
        $archivo = $textos->first(fn ($r) => is_array($r) && ($r['tipo'] ?? '') === 'archivo');

        $this->assertStringContainsString('/tienda/televisores', $plano);
        $this->assertNotNull($archivo);
        $this->assertStringContainsString('televisores', $archivo['nombre']);
        $this->assertStringNotContainsString('distintas secciones', $plano, 'Única categoría: no se pregunta.');
        $this->sinFlujoViejo($plano);
    }

    // ═══ ESCENARIO D: sin categoría → honesto y simple ══════════════════════

    public function test_d_consulta_inexistente_mensaje_simple(): void
    {
        $txt = $this->conversar(['hola', '1', 'xxxxx']);

        $this->assertStringContainsString('No encontré una categoría relacionada con "xxxxx"', $txt);
        $this->assertStringNotContainsString('Producto de', $txt, 'No inventa alternativas.');
        $this->sinFlujoViejo($txt);
    }

    /** Fuera de rango al elegir sección: orienta sin romper. */
    public function test_seccion_fuera_de_rango(): void
    {
        $txt = $this->conversar(['hola', '1', 'cama', '9']);

        $this->assertStringContainsString('no está en la lista', $txt);
    }

    // ═══ MENÚ (se conserva del contrato anterior) ═══════════════════════════

    public function test_menu_01_primer_hola_presenta_completo(): void
    {
        $txt = $this->conversar(['hola']);

        $this->assertStringContainsString('Soy el asistente virtual de *MegaHogar Test*', $txt);
        $this->assertStringContainsString('Menú', $txt);
    }

    public function test_menu_02_menu_repetido_es_breve(): void
    {
        $estado = null;
        $this->conversar(['hola'], $estado);
        $txt = $this->conversar(['menu'], $estado);

        $this->assertStringNotContainsString('Soy el asistente virtual', $txt);
        $this->assertStringContainsString('Claro 👇', $txt);
        $this->assertStringContainsString('Buscar un producto', $txt);
    }

    public function test_menu_04_limpia_el_subflujo(): void
    {
        $estado = null;
        $this->conversar(['hola', '1', 'cama'], $estado);   // esperando sección
        $this->conversar(['menu'], $estado);
        $txt = $this->conversar(['3'], $estado);            // 3 = Métodos de pago

        $this->assertStringNotContainsString('Camarotes', $txt, 'El 3 no elige de una lista muerta.');
    }

    /** El título de una fila escrito a mano es la elección, no una búsqueda. */
    public function test_titulo_de_fila_escrito_no_se_busca(): void
    {
        $txt = $this->conversar(['hola', 'menu', '🔎 Buscar un producto']);

        $this->assertStringNotContainsString('No encontré', $txt);
        $this->assertMatchesRegularExpression('/buscas|producto o la categoría/i', $txt);
    }

    // ═══ INTENCIONES QUE NO SON BÚSQUEDA (intactas) ═════════════════════════

    public function test_faq_typos_y_direccion_siguen_funcionando(): void
    {
        $txt = $this->conversar(['hola', 'hacen entrega e instalacion?']);
        $this->assertStringContainsString('instalación del mueble', $txt);

        $txt = $this->conversar(['hola', 'donde kedan']);
        $this->assertStringContainsString('Odonovan 174', $txt);
    }

    /** Dentro de la búsqueda, una intención clara interrumpe (dirección/asesor). */
    public function test_intencion_clara_interrumpe_la_busqueda(): void
    {
        $txt = $this->conversar(['hola', '1', 'donde estan?']);
        $this->assertStringContainsString('Odonovan 174', $txt);

        $txt = $this->conversar(['hola', '1', 'asesor']);
        $this->assertStringContainsString('persona', mb_strtolower($txt));
    }

    /** "?" dentro de la búsqueda responde contextual. */
    public function test_signo_de_pregunta_contextual(): void
    {
        $txt = $this->conversar(['hola', '1', '?']);

        $this->assertStringContainsString('nombre del producto o la categoría', $txt);
        $this->assertStringNotContainsString('No te entendí', $txt);
    }

    /** El menú no es barrera: búsqueda directa sin pasar por él. */
    public function test_busqueda_directa_sin_menu(): void
    {
        $txt = $this->conversar(['televisores']);

        $this->assertStringContainsString('/tienda/televisores', $txt);
        $this->sinFlujoViejo($txt);
    }

    /** Typo real de cliente: "television" debe llegar a Televisores. */
    public function test_c_typo_television_llega_a_televisores(): void
    {
        $txt = $this->conversar(['hola', '1', 'television']);

        $this->assertStringContainsString('/tienda/televisores', $txt);
        $this->assertStringNotContainsString('No encontré una categoría', $txt);
        $this->sinFlujoViejo($txt);
    }

    /** La ruta del PDF exige firma válida y respeta el tenant. */
    public function test_ruta_pdf_firmada_y_con_tenant(): void
    {
        $cat = Category::where('project_id', $this->project->id)->where('name', 'Camarotes')->first();

        $this->get("/{$this->project->slug}/catalogo-pdf/{$cat->slug}")->assertStatus(403);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'publico.catalogo.pdf', now()->addHour(),
            ['slug' => $this->project->slug, 'categoria' => $cat->slug]);
        $r = $this->get($url);
        $r->assertOk();
        $this->assertStringContainsString('application/pdf', $r->headers->get('Content-Type'));

        $otro = Project::create(['owner_id' => User::factory()->create()->id,
            'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        $urlAjena = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'publico.catalogo.pdf', now()->addHour(),
            ['slug' => $otro->slug, 'categoria' => $cat->slug]);
        $this->get($urlAjena)->assertStatus(404);
    }
}
