<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Tienda\Support\StorefrontSections;
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

    public function test_admin_shell_exposes_one_accessible_mobile_drawer(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee('data-mobile-sidebar-trigger', false)
            ->assertSee('type="button"', false)
            ->assertSee('aria-controls="admin-sidebar"', false)
            ->assertSee(':aria-expanded="sidebarOpen.toString()"', false)
            ->assertSee('id="admin-sidebar"', false)
            ->assertSee('aria-label="Navegación principal"', false)
            ->assertSee('data-mobile-sidebar-close', false)
            ->assertSee('aria-label="Cerrar navegación principal"', false)
            ->assertSee('data-mobile-sidebar-overlay', false)
            ->assertSee('@keydown.escape.window="closeSidebar()"', false);

        $this->assertSame(1, substr_count($html, 'id="admin-sidebar"'));
        $this->assertSame(1, preg_match_all('/<[^>]+\sdata-admin-shell(?:\s|>)/', $html));
        $this->assertStringContainsString("document.body.classList.toggle('admin-sidebar-open'", $html);
        $this->assertStringContainsString('this.$refs.sidebarClose?.focus()', $html);
        $this->assertStringContainsString('this.sidebarTrigger || this.$refs.sidebarTrigger', $html);
        $this->assertStringContainsString('@media (prefers-reduced-motion:reduce)', $html);
        $this->assertStringContainsString('.sb-bixo.is-mobile-open', $html);
        $this->assertStringContainsString('width:min(20rem, calc(100vw - 3rem))', $html);
        $this->assertStringNotContainsString('|| mob', $html);
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

    /**
     * CADA CARA CON SU DISEÑO (decisión del usuario, 2026-08-30).
     *
     * Un intento previo sirvió la configuración con el shell comercial y eso
     * le llevó al panel el encabezado y las alertas de ventas, además de
     * quitarle el selector de negocio. Revertido: /bixoadmin conserva SU
     * shell —el del panel— en todas sus pantallas (por eso no hay saltos de
     * menú), y /bixosales conserva el suyo.
     */
    public function test_regular_settings_screen_uses_the_same_single_shell(): void
    {
        [$owner, $project] = $this->adminContext();

        $response = $this->actingAs($owner)
            ->withSession([
                'active_project_id'    => $project->id,
                'comercial_project_id' => $project->id,
            ])
            ->get(route('settings', ['p' => $project->id]))
            ->assertOk()
            ->assertSee('id="admin-sidebar"', false);

        $html = $response->getContent();
        // Un solo menú en la pantalla: ni rastro del shell de la otra cara.
        $this->assertSame(1, substr_count($html, 'id="admin-sidebar"'));
        $this->assertSame(0, substr_count($html, 'nav-marca-texto'),
            'La configuración no debe montar el shell de ventas');
    }
}
