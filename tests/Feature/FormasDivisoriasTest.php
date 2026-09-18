<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las formas divisorias entre secciones del Inicio.
 *
 * Se dibujan solo con CSS sobre `[data-store-home-section]`, que es el atributo
 * que ya lleva cada bloque. Lo que se protege aquí: que la forma elegida llegue
 * a la tienda, que un valor inventado no pinte nada, y que las medidas se
 * queden dentro de un rango sensato — una forma de 4000 px taparía la sección
 * entera.
 */
class FormasDivisoriasTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string,string>  $ajustes */
    private function html(array $ajustes): string
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Formas', 'slug' => 'fm-'.uniqid(), 'is_active' => true,
        ]);
        foreach ($ajustes as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }

        return view('tienda::components.storefront-shapes', [
            'settings' => $ajustes, 'project' => $project,
        ])->render();
    }

    /** La forma elegida se dibuja sobre el bloque que la pidió. */
    public function test_la_forma_se_emite_en_su_bloque(): void
    {
        $html = $this->html(['shape_hero_style' => 'waves']);

        $this->assertStringContainsString('[data-store-home-section="hero"]::after', $html);
        $this->assertStringContainsString('image/svg+xml', $html, 'La forma va en SVG incrustado.');
        $this->assertStringContainsString('height: 60px', $html, 'Altura por defecto.');
    }

    /** Cada bloque lleva la suya, sin pisarse. */
    public function test_cada_bloque_lleva_la_suya(): void
    {
        $html = $this->html([
            'shape_hero_style' => 'curve',
            'shape_benefits_style' => 'tilt',
            'shape_benefits_height' => '90',
        ]);

        $this->assertStringContainsString('[data-store-home-section="hero"]::after', $html);
        $this->assertStringContainsString('[data-store-home-section="benefits"]::after', $html);
        $this->assertStringContainsString('height: 90px', $html);
    }

    /** Sin nada configurado no se emite ni una regla. */
    public function test_sin_formas_no_emite_nada(): void
    {
        $this->assertStringNotContainsString('<style', $this->html([]));
    }

    /** Una forma que no está en el catálogo no se dibuja. */
    public function test_una_forma_inventada_no_se_emite(): void
    {
        $html = $this->html(['shape_hero_style' => 'url(javascript:alert(1))']);

        $this->assertStringNotContainsString('javascript', $html);
        $this->assertStringNotContainsString('data-store-home-section="hero"', $html);
    }

    /** La altura se acota: una forma gigante taparía el contenido. */
    public function test_la_altura_se_acota(): void
    {
        $this->assertStringContainsString('height: 160px',
            $this->html(['shape_hero_style' => 'waves', 'shape_hero_height' => '4000']));

        $this->assertStringContainsString('height: 20px',
            $this->html(['shape_hero_style' => 'waves', 'shape_hero_height' => '1']));
    }

    /** El color viene del fondo del negocio, y solo si es un color de verdad. */
    public function test_un_color_invalido_cae_al_blanco(): void
    {
        $html = $this->html([
            'shape_hero_style' => 'waves',
            'surface_color' => "red;}body{display:none",
        ]);

        $this->assertStringNotContainsString('display:none', $html);
        $this->assertStringContainsString('%23ffffff', $html, 'Cae al blanco.');
    }

    /** El control vive en 03 Página de inicio, junto a la animación del bloque. */
    public function test_el_control_esta_en_la_pagina_de_inicio(): void
    {
        $vista = file_get_contents(app_path('Modules/Tienda/Views/settings/builder/stages/home.blade.php'));

        foreach (['_style', '_height', '_flip'] as $sufijo) {
            $this->assertStringContainsString("'shape_'+editingBlock.component+'{$sufijo}'", $vista,
                "Sin editor para shape_*{$sufijo}.");
        }
    }

    /** El velo se pinta detrás del contenido, nunca encima. */
    public function test_el_velo_no_tapa_el_contenido(): void
    {
        $html = $this->html(['overlay_hero_opacity' => '40']);

        $this->assertStringContainsString('[data-store-home-section="hero"]::before', $html);
        $this->assertStringContainsString('opacity: 0.4', $html);
        $this->assertStringContainsString('[data-store-home-section="hero"] > * { position: relative; z-index: 1; }',
            $html, 'Sin subir el contenido, el velo lo taparía.');
    }

    /** Intensidad 0 es "sin velo": no se emite nada. */
    public function test_intensidad_cero_no_emite_velo(): void
    {
        $this->assertStringNotContainsString('::before', $this->html(['overlay_hero_opacity' => '0']));
    }

    /** El velo se acota: al 100 % el fondo desaparecería. */
    public function test_el_velo_se_acota(): void
    {
        $this->assertStringContainsString('opacity: 0.9',
            $this->html(['overlay_hero_opacity' => '100']));
    }

    /** Un color con inyección cae al de por defecto. */
    public function test_un_color_de_velo_invalido_cae_al_defecto(): void
    {
        $html = $this->html([
            'overlay_hero_opacity' => '40',
            'overlay_hero_color' => 'red;}body{display:none',
        ]);

        $this->assertStringNotContainsString('}body{', $html);
        $this->assertStringContainsString('#0f172a', $html);
    }
}
