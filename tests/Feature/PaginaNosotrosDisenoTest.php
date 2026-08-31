<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\StorePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Diseño por defecto de la página "Nosotros" (2026-08-31).
 *
 * Historia, Valores y Equipo se pintaban como un párrafo plano separado por
 * una línea gris de ancho completo: los valores —que el negocio escribe uno
 * por línea— salían como un bloque de texto corrido indistinguible, y la
 * página entera quedaba sin jerarquía.
 */
class PaginaNosotrosDisenoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería del Centro',
            'slug' => 'ferreteria-diseno-nosotros',
            'is_active' => true,
        ]);
        $this->project->settings()->create(['key' => 'catalog_template', 'value' => 'computienda']);
    }

    private function nosotros(array $contenido): string
    {
        StorePage::create([
            'project_id' => $this->project->id, 'key' => 'nosotros',
            'title' => 'Nosotros', 'content' => $contenido, 'is_enabled' => true,
        ]);

        return $this->get('/'.$this->project->slug.'/nosotros')->assertOk()->getContent();
    }

    public function test_los_valores_se_pintan_como_lista_y_no_como_parrafo_corrido(): void
    {
        $html = $this->nosotros([
            'values' => "Honestidad en lo que ofrecemos\nAtención cercana y sin prisas\nCalidad comprobada",
        ]);

        $this->assertStringContainsString('class="sp-values"', $html,
            'Los valores deben salir como lista, no como un párrafo');

        // Un item por línea escrita, ni más ni menos. Se cuenta DENTRO de la
        // lista: la página trae otros <li> (menú, categorías) que no son esto.
        $lista = substr($html, strpos($html, 'class="sp-values"'));
        $lista = substr($lista, 0, strpos($lista, '</ul>'));
        $this->assertSame(3, substr_count($lista, '<li>'));

        foreach (['Honestidad en lo que ofrecemos', 'Atención cercana y sin prisas', 'Calidad comprobada'] as $valor) {
            $this->assertStringContainsString($valor, $lista);
        }
    }

    /** Un solo valor no necesita lista: sigue siendo un párrafo. */
    public function test_un_valor_suelto_no_se_convierte_en_lista(): void
    {
        $html = $this->nosotros(['values' => 'Honestidad en lo que ofrecemos']);

        $this->assertStringNotContainsString('class="sp-values"', $html);
        $this->assertStringContainsString('Honestidad en lo que ofrecemos', $html);
    }

    /** Historia y equipo conservan su párrafo, con ancho de lectura acotado. */
    public function test_historia_y_equipo_siguen_siendo_texto_con_ancho_de_lectura(): void
    {
        $html = $this->nosotros([
            'history' => 'Empezamos en 2019 con un local pequeño.',
            'team'    => 'Somos seis personas que conocen el oficio.',
        ]);

        $this->assertStringContainsString('Empezamos en 2019 con un local pequeño.', $html);
        $this->assertStringContainsString('Somos seis personas que conocen el oficio.', $html);
        $this->assertStringContainsString('max-width:70ch', $html,
            'Sin ancho de lectura la línea cruza toda la pantalla y cansa la vista');
    }

    /** La jerarquía la marca el título con la barra de la MARCA, no una línea gris. */
    public function test_el_titulo_lleva_la_barra_de_la_marca(): void
    {
        $html = $this->nosotros(['history' => 'Una historia corta.']);

        $this->assertStringContainsString('.store-page-block h2::after', $html);
        $this->assertStringContainsString('background:var(--primary)', $html,
            'El acento debe salir del color de marca del negocio, nunca fijo');
    }
}
