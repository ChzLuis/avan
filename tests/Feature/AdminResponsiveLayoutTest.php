<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function adminContext(): array
    {
        // La pantalla clasica solo es alcanzable por superadmin con ?classic=1.
        $owner = User::factory()->create(['is_superadmin' => 1]);
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Tienda responsive',
            'slug' => 'tienda-responsive',
            'is_active' => true,
        ]);

        $project->settings()->createMany([
            ['key' => 'catalog_template', 'value' => 'computienda'],
            ['key' => 'primary_color', 'value' => '#2563eb'],
        ]);
        StorefrontSections::ensure($project);

        return [$owner, $project];
    }

    private function designer(string $section = 'plantilla')
    {
        [$owner, $project] = $this->adminContext();

        return $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('settings.design', ['s' => $section, 'classic' => 1]));
    }

    /**
     * SHELL ÚNICO (2026-08-30): todo el Workspace con negocio activo se sirve
     * con el MISMO shell. Antes convivían dos —el del panel (`#admin-sidebar`)
     * y el comercial— y el usuario saltaba de menú al navegar dentro de la
     * misma cara. El drawer accesible que se exige aquí es el del shell único.
     */
    public function test_admin_shell_exposes_one_accessible_mobile_drawer(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee('aria-controls="sidebar"', false)
            ->assertSee('type="button"', false)
            ->assertSee('aria-label="Abrir menú"', false)
            ->assertSee('id="sidebar"', false)
            ->assertSee('aria-label="Navegación principal"', false);

        // Un solo menú en la página: ni rastro del shell que se retiró.
        $this->assertSame(1, substr_count($html, 'aria-label="Navegación principal"'));
        $this->assertSame(0, substr_count($html, 'id="admin-sidebar"'),
            'Dos shells en la misma pantalla = el usuario salta de menú al navegar');
        $this->assertStringContainsString('nav-movil-abierto', $html);
    }

    public function test_designer_keeps_two_tabs_and_exactly_three_supported_templates(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();

        $this->assertSame(2, substr_count($html, 'data-primary-designer-tab='));
        $this->assertSame(3, substr_count($html, 'data-supported-template-card='));
        foreach (['ecommerce', 'direct', 'computienda'] as $template) {
            $response->assertSee('data-supported-template-card="'.$template.'"', false);
        }

        $response
            ->assertSee('data-designer-section="plantilla"', false)
            ->assertSee('data-supported-template-selector', false)
            ->assertSee('data-template-feedback="success"', false)
            ->assertSee('Ver cambio en la tienda');
        $this->assertStringContainsString('.designer-shell form .grid > * { min-width:0; }', $html);
    }

    public function test_constructor_and_design_save_routes_are_unchanged(): void
    {
        $response = $this->designer('constructor')->assertOk();

        $response
            ->assertSee('data-designer-section="constructor"', false)
            ->assertSee('data-constructor-nav', false)
            ->assertSee('CONSTRUCTOR VISUAL')
            ->assertSee('action="'.route('settings.design.update').'"', false)
            ->assertSee('action="'.route('settings.experience.popup').'"', false)
            ->assertSee('action="'.route('settings.experience.home.publishAll').'"', false);

        $this->assertSame('/bixoadmin/settings/design', route('settings.design.update', [], false));
    }

    public function test_regular_settings_screen_uses_the_same_single_shell(): void
    {
        // Unificación del Workspace (2026-08-29): "Mi negocio" ya no renderiza
        // en el shell del panel sino en el shell comercial UNIFICADO — el mismo
        // que la operación (bixosales). El contrato pasa a ser: un solo shell
        // para el tenant, con el sidebar maestro (nav-marca) y sin el aside del
        // panel viejo. Ver WorkspaceShellUnificadoTest.
        [$owner, $project] = $this->adminContext();

        $response = $this->actingAs($owner)
            ->withSession([
                'active_project_id'    => $project->id,
                'comercial_project_id' => $project->id,
            ])
            ->get(route('settings', ['p' => $project->id]))
            ->assertOk()
            ->assertSee('nav-marca-texto', false);

        $this->assertSame(0, substr_count($response->getContent(), 'id="admin-sidebar"'),
            'settings ya no debe montar el sidebar del panel viejo');
    }
}
