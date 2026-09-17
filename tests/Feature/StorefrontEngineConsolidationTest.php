<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontEngineConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_es_simulacion_por_defecto(): void
    {
        $project = $this->project('legacy', 'computienda');

        $this->artisan('storefront:consolidate-engines')->assertSuccessful();

        $this->assertSame('computienda', $project->fresh()->setting('catalog_template'));
        $this->assertNull($project->fresh()->setting('catalog_template_before_engine_consolidation'));
    }

    public function test_convierte_solo_computienda_y_conserva_ajustes_explicitos(): void
    {
        $legacy = $this->project('legacy-aplicar', 'computienda');
        $legacy->settings()->create(['key' => 'theme_preset', 'value' => 'custom-brand']);
        $legacyWithoutOverrides = $this->project('legacy-sin-ajustes', 'computienda');
        $direct = $this->project('directo-intacto', 'direct');

        $this->artisan('storefront:consolidate-engines', ['--apply' => true])->assertSuccessful();

        $legacy->refresh();
        $this->assertSame('ecommerce', $legacy->setting('catalog_template'));
        $this->assertSame('computienda', $legacy->setting('catalog_template_before_engine_consolidation'));
        $this->assertSame('custom-brand', $legacy->setting('theme_preset'));
        $this->assertSame('tech', $legacy->setting('product_card_style'));
        $this->assertSame('ecommerce', $legacyWithoutOverrides->fresh()->setting('catalog_template'));
        $this->assertSame('tech-dark', $legacyWithoutOverrides->setting('theme_preset'));
        $this->assertSame('tech', $legacyWithoutOverrides->setting('product_card_style'));
        $this->assertSame('direct', $direct->fresh()->setting('catalog_template'));
    }

    public function test_puede_limitarse_a_un_proyecto_por_slug(): void
    {
        $first = $this->project('primero', 'computienda');
        $second = $this->project('segundo', 'computienda');

        $this->artisan('storefront:consolidate-engines', ['--project' => 'primero', '--apply' => true])
            ->assertSuccessful();

        $this->assertSame('ecommerce', $first->fresh()->setting('catalog_template'));
        $this->assertSame('computienda', $second->fresh()->setting('catalog_template'));
    }

    public function test_directo_muestra_productos_sin_categoria_en_lugar_de_ocultarlos(): void
    {
        $project = $this->project('directo-sin-categorias', 'direct');
        $project->products()->create([
            'name' => 'Producto todavía sin categoría',
            'price' => 49.90,
            'stock' => 5,
            'is_available' => true,
        ]);

        $this->get('/'.$project->slug)
            ->assertOk()
            ->assertSee('Producto todavía sin categoría');
    }

    public function test_ecommerce_respeta_inicio_y_tienda_como_rutas_independientes(): void
    {
        $project = $this->project('ecommerce-rutas', 'ecommerce');
        $project->products()->create([
            'name' => 'Producto de ruta independiente',
            'price' => 79.90,
            'stock' => 4,
            'is_available' => true,
        ]);

        $this->get('/'.$project->slug)
            ->assertOk()
            ->assertSee('const EC_INITIAL_PAGE = "home"', false)
            ->assertSee('href="http://localhost/ecommerce-rutas"', false)
            ->assertSee('href="http://localhost/ecommerce-rutas/tienda"', false)
            ->assertSee('Producto de ruta independiente');

        $this->get('/'.$project->slug.'/tienda')
            ->assertOk()
            ->assertSee('const EC_INITIAL_PAGE = "catalog"', false)
            ->assertSee('href="http://localhost/ecommerce-rutas"', false)
            ->assertSee('href="http://localhost/ecommerce-rutas/tienda"', false)
            ->assertSee('Todos los productos')
            ->assertSee('Producto de ruta independiente');
    }

    public function test_ecommerce_aplica_el_preset_tecnologico_sin_usar_un_tercer_motor(): void
    {
        $project = $this->project('ecommerce-tech', 'ecommerce');
        $project->settings()->create(['key' => 'theme_preset', 'value' => 'tech-dark']);
        $project->settings()->create(['key' => 'product_card_style', 'value' => 'tech']);

        $this->get('/'.$project->slug)
            ->assertOk()
            ->assertViewIs('public.templates.ecommerce')
            ->assertSee('class="theme-tech-dark card-style-tech"', false)
            ->assertSee('--bg-body: #0b1220', false);
    }

    private function project(string $slug, string $template): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda '.$slug,
            'slug' => $slug,
            'is_active' => true,
        ]);
        $project->settings()->create(['key' => 'catalog_template', 'value' => $template]);
        return $project;
    }
}
