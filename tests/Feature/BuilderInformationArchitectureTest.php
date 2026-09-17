<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Storefront\BuilderRuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderInformationArchitectureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Los DIEZ pasos del Constructor.
     *
     * "Encabezado y menú" era una segunda pestaña dentro de Apariencia y no se
     * encontraba: el negocio no daba con el editor del menú de su tienda.
     * Ahora es un paso propio y numerado.
     */
    public function test_el_constructor_expone_los_diez_pasos_en_el_orden_canonico(): void
    {
        $this->assertSame([
            'business', 'appearance', 'header', 'home', 'catalog', 'sales',
            'pages', 'legal', 'advanced', 'publish',
        ], array_keys(BuilderRuleRegistry::STAGES));

        $this->assertSame([
            'Datos del negocio', 'Apariencia', 'Encabezado y menú', 'Página de inicio', 'Catálogo', 'Venta',
            'Páginas', 'Footer y legales', 'Configuración', 'Revisar y publicar',
        ], array_column(BuilderRuleRegistry::STAGES, 'label'));

        $knownStages = array_keys(BuilderRuleRegistry::STAGES);
        foreach (BuilderRuleRegistry::rules() as $rule) {
            $this->assertContains($rule['stage'], $knownStages, "La regla {$rule['code']} apunta a una etapa inexistente.");
        }
    }

    public function test_la_interfaz_separa_paginas_legales_y_ofrece_preview_responsive(): void
    {
        $owner = User::factory()->create(['is_superadmin' => true]);
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Tienda arquitectura',
            'slug' => 'tienda-arquitectura',
            'is_active' => true,
        ]);
        $project->settings()->create(['key' => 'catalog_template', 'value' => 'ecommerce']);

        $response = $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('settings.builder'))
            ->assertOk()
            ->assertSee('MI TIENDA')
            ->assertSee('Encabezado y menú')
            ->assertSee('Footer y legales')
            ->assertSee('aria-label="Tablet"', false)
            // El encabezado y el menú son un paso propio: antes se afirmaba lo
            // contrario porque vivían escondidos en una pestaña de Apariencia.
            ->assertSee("stage==='header'", false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'name="key" value="contacto"'));
        $this->assertSame(1, substr_count($html, 'name="key" value="privacidad"'));
        $this->assertSame(1, substr_count($html, 'name="key" value="terminos"'));
    }
}
