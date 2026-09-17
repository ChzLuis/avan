<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vista Tienda "Profesional" (2026-09-06): cabecera de categoría con foto y
 * lema, y tarjeta con marca, código, unidad y disponibilidad. Todo se decide
 * desde el Constructor; sin elegirlo, el catálogo se ve como antes.
 */
class VistaTiendaProTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Distribuidora Norte', 'slug' => 'vt-'.uniqid(), 'is_active' => true,
        ]);
        $base = [
            'storefront_structure_v2' => '1',
            'catalog_template' => 'computienda',
            'store_mode' => 'quote',
            'quote_whatsapp' => '999888777',
        ];
        foreach ($ajustes + $base as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }
        $cat = Category::create(['project_id' => $project->id, 'name' => 'Cables y conductores', 'slug' => 'cables', 'is_active' => true]);
        Product::create([
            'project_id' => $project->id, 'category_id' => $cat->id, 'name' => 'Cable THHN 4 mm² Rojo',
            'sku' => '1231029731', 'unit' => 'Rollo x 100 m', 'price' => 0, 'stock' => 12, 'is_available' => true,
        ]);

        return $project;
    }

    private function catalogo(Project $p): string
    {
        return $this->get(route('public.catalog', $p->slug).'/tienda')->assertOk()->getContent();
    }

    public function test_la_tarjeta_profesional_muestra_codigo_unidad_y_disponibilidad(): void
    {
        $html = $this->catalogo($this->tienda(['product_card_style' => 'pro']));

        $this->assertMatchesRegularExpression('/<body[^>]*class="[^"]*\\bcards-pro\\b/', $html, 'el body no lleva la clase del estilo');
        $this->assertStringContainsString('Código: <b>1231029731</b>', $html);
        $this->assertStringContainsString('Rollo x 100 m', $html);
        $this->assertStringContainsString('Disponible para cotizar', $html, 'en cotización la disponibilidad habla de cotizar');
    }

    public function test_el_texto_de_disponibilidad_se_cambia_desde_el_constructor(): void
    {
        $html = $this->catalogo($this->tienda(['product_card_style' => 'pro', 'catalog_availability_text' => 'Stock inmediato']));
        $this->assertStringContainsString('Stock inmediato', $html);
        $this->assertStringNotContainsString('Disponible para cotizar', $html);
    }

    public function test_sin_elegir_el_estilo_pro_la_tarjeta_no_cambia(): void
    {
        $html = $this->catalogo($this->tienda());
        // Se mira el marcado, no el CSS: la hoja de estilos nombra las clases igual.
        $this->assertStringNotContainsString('<div class="pc-pro-meta">', $html);
        $this->assertDoesNotMatchRegularExpression('/<body[^>]*class="[^"]*\\bcards-pro\\b/', $html);
    }

    public function test_la_cabecera_con_foto_lleva_el_lema_en_lineas(): void
    {
        $html = $this->catalogo($this->tienda([
            'catalog_hero_style'    => 'imagen',
            'catalog_hero_image'    => 'uploads/demo/cables.jpg',
            'catalog_hero_slogan'   => 'Tu proyecto | nuestra | energía',
            'catalog_hero_subtitle' => 'Encuentra el cable ideal para tu proyecto.',
        ]));

        $this->assertStringContainsString('cat-banner--imagen', $html);
        $this->assertStringContainsString("--cat-hero:url('", $html);
        $this->assertStringContainsString('<div class="cat-banner-lema" aria-hidden="true"><span>Tu proyecto</span><span>nuestra</span><span>energía</span></div>', $html);
        $this->assertStringContainsString('Encuentra el cable ideal para tu proyecto.', $html);
    }

    public function test_la_paginacion_numerada_lleva_enlaces_reales_y_el_rango(): void
    {
        $p = $this->tienda(['catalog_pagination' => 'numbers']);
        // 12 por pagina: con 14 productos hay dos paginas.
        $cat = $p->categories()->first();
        for ($i = 2; $i <= 14; $i++) {
            Product::create(['project_id' => $p->id, 'category_id' => $cat->id, 'name' => "Cable $i", 'sku' => "SKU$i", 'price' => 0, 'stock' => 1, 'is_available' => true]);
        }

        $html = $this->catalogo($p);

        $this->assertStringContainsString('<nav class="catalog-paginas"', $html);
        $this->assertStringContainsString('lastPage: 2', $html);
        $this->assertStringContainsString("page='+n", $html, 'los numeros llevan ?page= real');
        $this->assertStringContainsString('Siguiente', $html);
        $this->assertStringNotContainsString('class="catalog-loadmore"', $html, 'con numeros no va el boton de cargar mas');
    }

    public function test_por_defecto_sigue_el_boton_de_cargar_mas(): void
    {
        $html = $this->catalogo($this->tienda());
        $this->assertStringContainsString('class="catalog-loadmore"', $html);
        $this->assertStringNotContainsString('<nav class="catalog-paginas"', $html);
    }

    /** Con precios de verdad para que el filtro tenga con que trabajar. */
    private function conPrecios(Project $p): void
    {
        $cat = $p->categories()->first();
        foreach ([15, 40, 90, 150, 320] as $i => $precio) {
            Product::create(['project_id' => $p->id, 'category_id' => $cat->id, 'name' => "Producto $i", 'sku' => "P$i", 'price' => $precio, 'stock' => 5, 'is_available' => true]);
        }
    }

    public function test_en_cotizacion_no_aparece_el_filtro_de_precio(): void
    {
        $p = $this->tienda(['store_mode' => 'quote']);
        $this->conPrecios($p);
        $html = $this->catalogo($p);

        $this->assertStringNotContainsString('<div class="catalog-price-inputs">', $html, 'una tienda por cotización no filtra por precio');
    }

    public function test_en_venta_directa_el_filtro_de_precio_sigue(): void
    {
        $p = $this->tienda(['store_mode' => 'direct']);
        $this->conPrecios($p);
        $html = $this->catalogo($p);

        $this->assertStringContainsString('<div class="catalog-price-inputs">', $html);
    }

    public function test_sin_foto_la_cabecera_con_imagen_cae_a_la_banda_simple(): void
    {
        $html = $this->catalogo($this->tienda(['catalog_hero_style' => 'imagen', 'catalog_hero_slogan' => 'Uno | dos']));
        $this->assertDoesNotMatchRegularExpression('/<div class="cat-banner[^"]*cat-banner--imagen/', $html);
        $this->assertStringNotContainsString('<div class="cat-banner-lema"', $html);
    }
}
