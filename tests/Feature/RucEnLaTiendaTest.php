<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\StorefrontLayoutPacks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RUC y razon social en la tienda (2026-09-07).
 *
 * En Peru el comercio debe identificarse en su web con razon social y RUC. No
 * puede depender de que el cliente eligiera "el pie correcto": TODAS las
 * variantes tienen que pintarlo. Seis de las quince no lo hacian, y ademas
 * `DatosPie` leia `legal_name` mientras el Constructor guardaba `razon_social`.
 */
class RucEnLaTiendaTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Mi Tienda', 'slug' => 'ruc-'.uniqid(), 'is_active' => true,
        ]);
        $base = [
            'storefront_structure_v2' => '1', 'catalog_template' => 'computienda',
            'ruc' => '20614995590', 'razon_social' => 'DISTRIBUIDORA MURUHUAY S.A.C.',
        ];
        foreach ($ajustes + $base as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => (string) $v]);
        }

        return $project;
    }

    private function portada(Project $p): string
    {
        return $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();
    }

    /** Todas las variantes del pie identifican al comercio. */
    public function test_todas_las_variantes_del_pie_muestran_el_ruc(): void
    {
        $variantes = array_keys(StorefrontLayoutPacks::options('footers'));
        $this->assertGreaterThan(10, count($variantes), 'se esperaban todas las variantes');

        foreach ($variantes as $variante) {
            $html = $this->portada($this->tienda(['footer_layout' => $variante]));
            $this->assertStringContainsString('20614995590', $html, "el pie '$variante' no muestra el RUC");
            $this->assertStringContainsString('DISTRIBUIDORA MURUHUAY S.A.C.', $html, "el pie '$variante' no muestra la razon social");
        }
    }

    /** La plantilla antigua `ecommerce` (Market Huacho) tambien identifica al comercio. */
    public function test_la_plantilla_ecommerce_tambien_muestra_el_ruc(): void
    {
        $p = $this->tienda(['catalog_template' => 'ecommerce']);
        $html = $this->portada($p);

        $this->assertStringContainsString('20614995590', $html);
        $this->assertStringContainsString('DISTRIBUIDORA MURUHUAY S.A.C.', $html);
    }

    /** La razon social sale de donde el Constructor la guarda. */
    public function test_la_razon_social_viene_del_constructor(): void
    {
        $html = $this->portada($this->tienda(['footer_layout' => 'classic']));
        $this->assertStringContainsString('DISTRIBUIDORA MURUHUAY S.A.C.', $html);
    }

    /** Sin razon social escrita se cae al nombre del negocio, no a un hueco. */
    public function test_sin_razon_social_usa_el_nombre_del_negocio(): void
    {
        $p = $this->tienda(['footer_layout' => 'classic', 'razon_social' => '']);
        $html = $this->portada($p);

        $this->assertStringContainsString('20614995590', $html);
        $this->assertStringContainsString('Mi Tienda', $html);
    }

    /** Un negocio sin RUC cargado no imprime "RUC" a secas. */
    public function test_sin_ruc_no_se_pinta_la_etiqueta_vacia(): void
    {
        $p = $this->tienda(['footer_layout' => 'classic', 'ruc' => '', 'razon_social' => '']);
        $html = $this->portada($p);

        $this->assertStringNotContainsString('RUC ·', $html);
        $this->assertStringNotContainsString('· RUC<', $html);
    }
}
