<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\StorePage;
use App\Models\User;
use App\Support\ContenidoEjemplo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Texto de ejemplo en las páginas de contenido sin llenar.
 *
 * Lo que se protege aquí: que una página a medio configurar salga con su
 * diseño en vez de un título suelto, que el relleno NUNCA pise lo que escribió
 * el negocio, y que ese relleno no meta latín ni invente datos de la empresa.
 */
class ContenidoEjemploPaginasTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería del Centro',
            'slug' => 'ferreteria-centro',
            'is_active' => true,
        ]);
        $this->project->settings()->create(['key' => 'catalog_template', 'value' => 'computienda']);
    }

    private function paginaNosotros(array $contenido = []): StorePage
    {
        return StorePage::create([
            'project_id' => $this->project->id, 'key' => 'nosotros',
            'title' => 'Nosotros', 'content' => $contenido, 'is_enabled' => true,
        ]);
    }

    public function test_rellena_los_campos_vacios_con_el_nombre_real_del_negocio(): void
    {
        [$contenido, $rellenados] = ContenidoEjemplo::completar([], 'nosotros', $this->project);

        foreach (['body', 'history', 'mission', 'vision', 'values', 'team'] as $campo) {
            $this->assertNotEmpty($contenido[$campo] ?? null, "Falta el ejemplo de {$campo}.");
            $this->assertContains($campo, $rellenados);
        }
        $this->assertStringContainsString('Ferretería del Centro', $contenido['body']);
    }

    public function test_el_relleno_no_es_lorem_ipsum_ni_inventa_datos_del_negocio(): void
    {
        [$contenido] = ContenidoEjemplo::completar([], 'nosotros', $this->project);
        $todo = json_encode($contenido, JSON_UNESCAPED_UNICODE);

        // Latín de imprenta en una página que ve el cliente se lee como error.
        $this->assertStringNotContainsStringIgnoringCase('lorem', $todo);
        $this->assertStringNotContainsStringIgnoringCase('ipsum', $todo);

        // Y no puede afirmar hechos que quizá sean falsos para ese negocio.
        $this->assertDoesNotMatchRegularExpression('/\b(19|20)\d{2}\b/', $todo, 'El ejemplo inventa un año.');
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(años|clientes|sedes|sucursales|premios)\b/iu', $todo);
    }

    public function test_nunca_sustituye_lo_que_escribio_el_negocio(): void
    {
        [$contenido, $rellenados] = ContenidoEjemplo::completar(
            ['body' => 'Somos la ferretería del barrio.', 'mission' => ''],
            'nosotros',
            $this->project,
        );

        $this->assertSame('Somos la ferretería del barrio.', $contenido['body']);
        $this->assertNotContains('body', $rellenados);
        $this->assertContains('mission', $rellenados, 'Un campo vacío sí debe rellenarse.');
    }

    public function test_el_negocio_puede_apagar_el_relleno(): void
    {
        $this->assertTrue(ContenidoEjemplo::activoEn($this->project), 'Debe venir activo por defecto.');

        $this->project->settings()->create(['key' => ContenidoEjemplo::AJUSTE, 'value' => '0']);
        $this->assertFalse(ContenidoEjemplo::activoEn($this->project->fresh()));

        [$contenido, $rellenados] = ContenidoEjemplo::completar([], 'nosotros', $this->project, false);
        $this->assertSame([], $contenido);
        $this->assertSame([], $rellenados);
    }

    public function test_la_pagina_publica_sale_con_diseno_en_vez_de_vacia(): void
    {
        $this->paginaNosotros([]);

        $html = $this->get('/'.$this->project->slug.'/nosotros')->assertOk()->getContent();

        $this->assertStringContainsString('productos de calidad', $html);
        $this->assertStringContainsStringIgnoringCase('historia', $html);
        $this->assertStringContainsStringIgnoringCase('misión', $html);
        $this->assertStringContainsStringIgnoringCase('valores', $html);
        $this->assertStringNotContainsStringIgnoringCase('lorem', $html);
    }

    public function test_en_la_tienda_manda_el_texto_del_negocio(): void
    {
        $this->paginaNosotros(['body' => 'Vendemos herramientas desde el mercado central.']);

        $html = $this->get('/'.$this->project->slug.'/nosotros')->assertOk()->getContent();

        $this->assertStringContainsString('herramientas desde el mercado central', $html);
        // Y los huecos que siguen vacíos mantienen su ejemplo.
        $this->assertStringContainsStringIgnoringCase('misión', $html);
    }

    public function test_el_relleno_no_se_guarda_en_la_base(): void
    {
        $pagina = $this->paginaNosotros([]);

        $this->get('/'.$this->project->slug.'/nosotros')->assertOk();

        // Solo se completa en memoria: si se guardara, el día que el negocio
        // escriba lo suyo habría que ir limpiando relleno por la base.
        $this->assertSame([], $pagina->fresh()->content ?? []);
    }

    public function test_terminos_y_privacidad_tambien_tienen_ejemplo_propio(): void
    {
        foreach (['terminos', 'privacidad'] as $clave) {
            [$contenido] = ContenidoEjemplo::completar([], $clave, $this->project);
            $this->assertNotEmpty($contenido['body'] ?? null, "Falta el ejemplo de {$clave}.");
            $this->assertStringNotContainsStringIgnoringCase('lorem', $contenido['body']);
        }
    }
}
