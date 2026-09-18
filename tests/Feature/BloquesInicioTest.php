<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Modules\Tienda\Models\StoreSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Todo bloque que el Constructor deja configurar tiene que salir en la tienda.
 *
 * El Constructor ofrecía diecinueve bloques y el renderizador compartido solo
 * pintaba ocho: los otros once vivían únicamente dentro de la plantilla
 * `computienda`. Un negocio en cualquier otra plantilla podía cargar sus
 * preguntas frecuentes, sus testimonios o su galería, guardarlo, y no salía
 * nada — sin un solo aviso.
 *
 * Es la misma familia de fallo que la analítica y el menú: el ajuste existe, se
 * guarda y no llega. Este test cierra la puerta.
 */
class BloquesInicioTest extends TestCase
{
    use RefreshDatabase;

    /** Contenido mínimo para que cada bloque tenga algo que pintar. */
    private const MUESTRAS = [
        'about_preview' => ['title' => 'SOMOS BLOQUE', 'body' => 'Texto de prueba',
            'items' => [['enabled' => true, 'value' => '25', 'title' => 'años']]],
        'brands' => ['title' => 'MARCAS BLOQUE',
            'items' => [['enabled' => true, 'name' => 'Acme', 'image' => 'marcas/acme.png']]],
        'collection_showcase' => ['title' => 'COLECCIONES BLOQUE',
            'items' => [['enabled' => true, 'title' => 'Verano', 'image' => 'col/verano.jpg']]],
        'cta_banner' => ['title' => 'LLAMADA BLOQUE', 'button_text' => 'Ir', 'button_url' => '/x'],
        'faq' => ['title' => 'PREGUNTAS BLOQUE',
            'items' => [['enabled' => true, 'question' => '¿Envían?', 'answer' => 'Sí']]],
        'gallery' => ['title' => 'GALERIA BLOQUE',
            'items' => [['enabled' => true, 'image' => 'gal/1.jpg', 'caption' => 'Foto']]],
        'info_strip' => ['title' => 'DATOS BLOQUE',
            'items' => [['enabled' => true, 'title' => 'Envío gratis', 'description' => 'Desde S/ 100']]],
        'media_banner' => ['title' => 'MEDIA BLOQUE', 'desktop_image' => 'banners/a.jpg'],
        'testimonials' => ['title' => 'TESTIMONIOS BLOQUE',
            'items' => [['enabled' => true, 'name' => 'Ana', 'text' => 'Muy buena atención', 'rating' => 5]]],
        'wa_advisory' => ['title' => 'ASESORIA BLOQUE', 'phone' => '999111222'],
        'locations' => ['title' => 'SEDES BLOQUE',
            'items' => [['enabled' => true, 'name' => 'Central', 'address' => 'Av. Uno 1']]],
    ];

    private function tiendaCon(string $componente, array $contenido): string
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Bloques', 'slug' => 'blq-'.uniqid(), 'is_active' => true,
        ]);
        $project->settings()->create(['key' => 'storefront_structure_v2', 'value' => '1']);

        StoreSection::create([
            'project_id' => $project->id, 'component' => $componente,
            'content' => $contenido, 'is_enabled' => true, 'sort_order' => 1,
        ]);

        return $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();
    }

    /** Cada bloque configurable llega a la tienda. */
    public function test_todos_los_bloques_configurables_se_pintan(): void
    {
        $mudos = [];

        foreach (self::MUESTRAS as $componente => $contenido) {
            $html = $this->tiendaCon($componente, $contenido);
            if (! str_contains($html, 'data-store-home-section="'.$componente.'"')) {
                $mudos[] = $componente;
            }
        }

        $this->assertSame([], $mudos,
            'Estos bloques se configuran y no salen en la tienda: '.implode(', ', $mudos));
    }

    /** Y el contenido escrito por el negocio es el que se ve. */
    public function test_el_contenido_del_negocio_es_el_que_sale(): void
    {
        foreach (self::MUESTRAS as $componente => $contenido) {
            $html = $this->tiendaCon($componente, $contenido);
            $this->assertStringContainsString($contenido['title'], $html,
                "El bloque {$componente} no muestra su título.");
        }
    }

    /** Un bloque apagado no se pinta, aunque tenga contenido. */
    public function test_un_bloque_apagado_no_sale(): void
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Apagada', 'slug' => 'apg-'.uniqid(), 'is_active' => true,
        ]);
        $project->settings()->create(['key' => 'storefront_structure_v2', 'value' => '1']);

        StoreSection::create([
            'project_id' => $project->id, 'component' => 'faq',
            'content' => ['title' => 'NO DEBE SALIR', 'items' => [['enabled' => true, 'question' => 'x', 'answer' => 'y']]],
            'is_enabled' => false, 'sort_order' => 1,
        ]);

        $html = $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('NO DEBE SALIR', $html);
    }

    /**
     * El renderizador cubre todo lo que el Constructor deja editar.
     *
     * Compara las dos listas para que, si mañana alguien añade un editor sin
     * su render, salte aquí y no en la tienda de un cliente.
     */
    public function test_el_renderizador_cubre_lo_que_el_constructor_ofrece(): void
    {
        $editor = file_get_contents(app_path('Modules/Tienda/Views/settings/builder/stages/home.blade.php'));
        preg_match_all("/editingBlock\.component==='([a-z_]+)'/", $editor, $m);
        $ofrecidos = array_unique($m[1]);

        $render = file_get_contents(app_path('Modules/Tienda/Views/components/storefront-home-sections.blade.php'));
        preg_match_all("/\\\$section->component === '([a-z_]+)'/", $render, $r);
        $pintados = array_unique($r[1]);

        $faltan = array_values(array_diff($ofrecidos, $pintados));

        $this->assertSame([], $faltan,
            'Tienen editor pero el renderizador no los pinta: '.implode(', ', $faltan));
    }
}
