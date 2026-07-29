<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicController;
use App\Http\Controllers\SettingsController;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\StorePopup;
use App\Models\StoreSection;
use App\Models\User;
use App\Support\CatalogTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GlobalTemplateSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_design_settings_survive_every_supported_template_change(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Tienda de prueba',
            'slug' => 'tienda-prueba',
            'is_active' => true,
        ]);

        $project->settings()->createMany([
            ['key' => 'primary_color', 'value' => '#123456'],
            ['key' => 'hero_title', 'value' => 'Portada personalizada'],
            ['key' => 'logo_url', 'value' => 'logos/mi-logo.png'],
            ['key' => 'catalog_cols_desktop', 'value' => '5'],
            ['key' => 'checkout_fields', 'value' => json_encode(['fixed' => [], 'custom' => []])],
        ]);
        $popup = StorePopup::create([
            'project_id' => $project->id,
            'title' => 'Promoción global',
            'delay_seconds' => 0,
            'frequency' => 'always',
            'show_desktop' => true,
            'show_mobile' => true,
            'is_enabled' => true,
        ]);
        StoreSection::create([
            'project_id' => $project->id,
            'page' => 'home',
            'component' => 'benefits',
            'content' => ['title' => 'Beneficios globales'],
            'sort_order' => 1,
            'is_enabled' => true,
            'show_desktop' => true,
            'show_tablet' => true,
            'show_mobile' => true,
        ]);

        $this->actingAs($user);
        app()->instance('active_project', $project);
        $controller = app(SettingsController::class);

        $templateKeys = CatalogTemplates::supportedKeys();
        $this->assertSame(['ecommerce', 'direct', 'computienda'], $templateKeys);
        foreach ($templateKeys as $template) {
            $request = Request::create('/settings/design/apply-template', 'POST', [
                'template' => $template,
                // Ni siquiera una petición antigua puede borrar la personalización.
                'overwrite' => '1',
            ]);
            $response = $controller->applyTemplate($request);

            $this->assertTrue($response->getData(true)['ok']);
            $this->assertSame($template, $project->setting('catalog_template'));
            $this->assertSame('#123456', $project->setting('primary_color'));
            $this->assertSame('Portada personalizada', $project->setting('hero_title'));
            $this->assertSame('logos/mi-logo.png', $project->setting('logo_url'));
            $this->assertSame('5', $project->setting('catalog_cols_desktop'));

        }

        $savedTemplate = ProjectTemplate::create([
            'project_id' => $project->id,
            'name' => 'Diseño guardado',
            'settings' => ['catalog_template' => 'direct', 'primary_color' => '#ffffff', 'hero_title' => 'No debe reemplazar'],
            'is_active' => false,
        ]);
        $customRequest = Request::create('/settings/design/apply-project-template', 'POST', [
            'id' => $savedTemplate->id,
            'apply_to_project' => '1',
            'overwrite' => '1',
        ]);
        $customResponse = $controller->applyProjectTemplate($customRequest);
        $this->assertTrue($customResponse->getData(true)['ok']);
        $this->assertSame('direct', $project->setting('catalog_template'));
        $this->assertSame('#123456', $project->setting('primary_color'));
        $this->assertSame('Portada personalizada', $project->setting('hero_title'));

        [$view, $data] = app(PublicController::class)->prepararCatalogo($project->fresh());
        $this->assertSame('public.templates.direct', $view);
        $this->assertSame('#123456', $data['settings']['primary_color']);
        $this->assertSame('Portada personalizada', $data['settings']['hero_title']);
        $this->assertSame('logos/mi-logo.png', $data['settings']['logo_url']);
        $this->assertSame($popup->id, $data['popup']->id);
        $this->assertCount(1, $data['sections']);
        $this->assertSame('Beneficios globales', $data['sections']->first()->content['title']);
    }

    public function test_saving_one_tab_does_not_reset_boolean_settings_from_another_tab(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Tienda de prueba',
            'slug' => 'tienda-pestanas',
            'is_active' => true,
        ]);
        $project->settings()->createMany([
            ['key' => 'hero_cta1_show', 'value' => '1'],
            ['key' => 'footer_show_social', 'value' => '1'],
            ['key' => 'float_wa_show', 'value' => '1'],
        ]);

        $this->actingAs($user);
        app()->instance('active_project', $project);
        $request = Request::create('/settings/design', 'POST', [
            '_design_tab' => 'catalogo',
            'catalog_section_title' => 'Productos elegidos',
            'float_wa_show' => '0',
        ]);
        $response = app(SettingsController::class)->updateDesign($request);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Productos elegidos', $project->setting('catalog_section_title'));
        $this->assertSame('0', $project->setting('float_wa_show'));
        $this->assertSame('1', $project->setting('hero_cta1_show'));
        $this->assertSame('1', $project->setting('footer_show_social'));
    }
}
