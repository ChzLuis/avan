<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\StoreSection;
use App\Models\User;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bloque "Delivery" de la portada (2026-09-06).
 *
 * Banda con el vehículo, una placa de color con dos líneas grandes y la
 * condición del envío al lado. Todo se escribe desde el Constructor: sin
 * texto ni color quemados.
 */
class BloqueDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function tiendaCon(array $contenido, string $plantilla = 'computienda'): string
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Delivery', 'slug' => 'dlv-'.uniqid(), 'is_active' => true,
        ]);
        $project->settings()->createMany([
            ['key' => 'storefront_structure_v2', 'value' => '1'],
            ['key' => 'catalog_template', 'value' => $plantilla],
        ]);
        StoreSection::create([
            'project_id' => $project->id, 'component' => 'delivery_banner',
            'content' => $contenido, 'is_enabled' => true, 'sort_order' => 1,
        ]);

        return $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();
    }

    private array $contenido = [
        'variant' => 'dark',
        'badge_line1' => 'Delivery', 'badge_line2' => 'a todo el Perú',
        'title' => 'Delivery gratis con pedidos superiores a S/ 300',
        'note'  => 'Si estás en Lima Metropolitana recibe tu pedido hoy mismo.',
        'badge_color' => '#ff2d16',
    ];

    public function test_el_bloque_llega_a_la_tienda_con_lo_que_se_escribio(): void
    {
        $html = $this->tiendaCon($this->contenido);

        $this->assertStringContainsString('data-store-native-section="delivery_banner"', $html);
        $this->assertStringContainsString('a todo el Perú', $html);
        $this->assertStringContainsString('Delivery gratis con pedidos superiores a S/ 300', $html);
        $this->assertStringContainsString('recibe tu pedido hoy mismo', $html);
        $this->assertStringContainsString('--xs-placa:#ff2d16', $html);
    }

    public function test_sin_color_propio_la_placa_usa_el_color_de_la_marca(): void
    {
        $html = $this->tiendaCon(['badge_line1' => 'Delivery', 'title' => 'Envíos a todo el país'] + ['badge_color' => '']);

        $this->assertStringContainsString('Envíos a todo el país', $html);
        $this->assertStringNotContainsString('--xs-placa:', $html);
    }

    public function test_sin_texto_no_se_pinta_una_banda_vacia(): void
    {
        $html = $this->tiendaCon(['badge_line1' => '', 'title' => '', 'note' => '']);

        // `xs-delivery` esta en la hoja de estilos pase lo que pase: lo que
        // dice si el bloque se pinto es su marca de seccion.
        $this->assertStringNotContainsString('data-store-native-section="delivery_banner"', $html);
    }

    public function test_el_bloque_esta_registrado_en_el_constructor(): void
    {
        $this->assertArrayHasKey('delivery_banner', StorefrontSections::COMPONENTS);
        $d = StorefrontSections::defaults(Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'X', 'slug' => 'x-'.uniqid(), 'is_active' => true,
        ]));
        $this->assertSame('dark', $d['delivery_banner']['variant']);
        $this->assertFalse($d['delivery_banner']['enabled'], 'nace apagado: nadie quiere una banda que no configuró');
    }
}
