<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StoreExperienceController;
use App\Models\Project;
use App\Models\StorePopup;
use App\Models\User;
use App\Support\CatalogTemplates;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StorefrontHomepageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        // La pantalla de Diseño clasico quedo retirada: `design()` redirige al
        // Constructor salvo que un SUPERADMIN pida `?classic=1`, que es la
        // salida de emergencia. Las pruebas que ejercen ese HTML necesitan por
        // tanto un superadmin; si no, solo verian el redirect.
        $user = User::factory()->create(['is_superadmin' => 1]);
        $project = Project::create([
            'owner_id' => $user->id,
            'name' => 'Tienda Builder',
            'slug' => 'tienda-builder',
            'is_active' => true,
        ]);
        $this->actingAs($user);
        app()->instance('active_project', $project);
        return $project;
    }

    private function heroPayload(string $title, string $action): array
    {
        return [
            'action' => $action,
            'is_enabled' => '1',
            'show_desktop' => '1',
            'show_tablet' => '1',
            'show_mobile' => '1',
            'sort_order' => 10,
            'content' => [
                'mode' => 'single',
                'autoplay' => '0',
                'interval' => 6,
                'single' => [
                    'title' => $title,
                    'body' => 'Contenido administrado desde el panel',
                    'desktop_image' => '',
                    'mobile_image' => '',
                    'primary_text' => 'Comprar',
                    'primary_url' => '#catalogo',
                    'primary_color' => '#2563eb',
                    'secondary_text' => 'Ver catálogo',
                    'secondary_url' => '#catalogo',
                    'secondary_color' => '#0f172a',
                ],
                'slides' => [],
            ],
        ];
    }

    /**
     * Lo que protege este contrato es que `ensure()` sea IDEMPOTENTE: una
     * seccion por componente registrado, la llames las veces que la llames.
     *
     * El numero estaba fijado a 8 y el canon crecio a 20, asi que chocaba con
     * su propia ultima asercion —que ya compara contra la lista viva—. Se
     * cuenta contra el canon: si manana se registra un componente mas, el
     * contrato sigue valiendo, y si `ensure()` duplicara algo, falla igual.
     */
    public function test_defaults_create_the_canonical_sections_once(): void
    {
        $project = $this->project();
        StorefrontSections::ensure($project);
        StorefrontSections::ensure($project);

        $sections = $project->storeSections()->where('page', 'home')->get();
        $this->assertCount(count(StorefrontSections::COMPONENTS), $sections,
            'una seccion por componente: ni de menos, ni duplicadas');
        $this->assertEqualsCanonicalizing(array_keys(StorefrontSections::COMPONENTS), $sections->pluck('component')->all());
    }

    public function test_draft_preview_publish_and_template_change_keep_the_same_content(): void
    {
        $project = $this->project();
        StorefrontSections::ensure($project);
        $hero = $project->storeSections()->where('component', 'hero')->firstOrFail();
        $hero->update(['is_enabled' => true]);
        $publishedTitle = data_get($hero->content, 'single.title');

        $draftRequest = Request::create('/settings/experience/home/hero', 'POST', $this->heroPayload('Portada en borrador', 'draft'));
        app(StoreExperienceController::class)->saveHomeSection($draftRequest, 'hero');
        $hero->refresh();
        $this->assertTrue($hero->has_draft);
        $this->assertSame($publishedTitle, data_get($hero->content, 'single.title'));
        $this->assertSame('Portada en borrador', data_get($hero->draft_content, 'single.title'));

        [, $publicData] = app(PublicController::class)->prepararCatalogo($project->fresh());
        [, $previewData] = app(PublicController::class)->prepararCatalogo($project->fresh(), true);
        $this->assertSame($publishedTitle, data_get($publicData['sections']->firstWhere('component', 'hero')->content, 'single.title'));
        $this->assertSame('Portada en borrador', data_get($previewData['sections']->firstWhere('component', 'hero')->content, 'single.title'));

        $publishRequest = Request::create('/settings/experience/home/hero', 'POST', $this->heroPayload('Portada publicada', 'publish'));
        app(StoreExperienceController::class)->saveHomeSection($publishRequest, 'hero');
        $hero->refresh();
        $this->assertFalse($hero->has_draft);
        $this->assertSame('Portada publicada', data_get($hero->content, 'single.title'));

        foreach (['direct', 'computienda', 'ecommerce'] as $template) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            [$view, $data] = app(PublicController::class)->prepararCatalogo($project->fresh());
            $this->assertStringContainsString($template === 'direct' ? 'direct' : $template, $view);
            $this->assertSame('Portada publicada', data_get($data['sections']->firstWhere('component', 'hero')->content, 'single.title'));
        }
    }

    public function test_admin_builder_and_public_store_render_without_errors(): void
    {
        $project = $this->project();
        $adminView = app(SettingsController::class)->design(Request::create('/settings/design', 'GET', ['classic' => 1]));
        $this->assertCount(8, $adminView->getData()['homeSections']);
        $this->assertTrue(
            $adminView->getData()['homeSections']->every(fn ($section) => $section->project_id === $project->id)
        );
        $adminHtml = view('settings.partials.home-builder', $adminView->getData())->render();
        $this->assertStringContainsString('CONSTRUCTOR VISUAL', $adminHtml);
        $this->assertStringContainsString('Productos con descuento', $adminHtml);
        $this->assertStringContainsString('Guardar y publicar', $adminHtml);

        $project->storeSections()->whereIn('component', ['hero', 'benefits', 'featured_categories'])->update(['is_enabled' => true]);
        $project->categories()->create(['name' => 'Categoría principal', 'is_active' => true, 'sort_order' => 1]);
        [, $data] = app(PublicController::class)->prepararCatalogo($project->fresh());
        $publicHtml = view('components.storefront-home-sections', $data)->render();
        $this->assertStringContainsString('data-store-home-section="hero"', $publicHtml);
        $this->assertStringContainsString('data-store-home-section="benefits"', $publicHtml);
        $this->assertStringContainsString('data-store-home-section="featured_categories"', $publicHtml);
    }

    public function test_home_builder_handles_an_explicitly_empty_collection(): void
    {
        $this->project();

        $html = view('settings.partials.home-builder', [
            'homeSections' => collect(),
            'storeSectionNames' => StorefrontSections::COMPONENTS,
            'storeCategories' => collect(),
            'storeProducts' => collect(),
        ])->render();

        $this->assertStringContainsString('No hay secciones de Inicio disponibles.', $html);
    }

    public function test_every_system_template_renders_with_the_builder_hero_without_orphaned_markup(): void
    {
        $project = $this->project();
        StorefrontSections::ensure($project);
        $project->storeSections()->where('component', 'hero')->update(['is_enabled' => true]);

        foreach (array_keys(CatalogTemplates::all()) as $template) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            [$view, $data] = app(PublicController::class)->prepararCatalogo($project->fresh());
            $html = view($view, $data)->render();

            $this->assertStringContainsString('data-store-home-section="hero"', $html, $template);
            $this->assertStringContainsString("document.querySelectorAll('[data-store-native-section]')", $html, $template);
        }
    }

    public function test_every_home_component_accepts_its_default_configuration(): void
    {
        $project = $this->project();
        StorefrontSections::ensure($project);
        foreach (StorefrontSections::defaults($project) as $component => $definition) {
            $request = Request::create('/settings/experience/home/'.$component, 'POST', [
                'action' => 'publish', 'is_enabled' => '1', 'show_desktop' => '1',
                'show_tablet' => '1', 'show_mobile' => '1', 'sort_order' => 10,
                'content' => $definition['content'],
            ]);
            app(StoreExperienceController::class)->saveHomeSection($request, $component);
        }

        $sections = $project->storeSections()->where('page', 'home')->get();
        // Contra el canon vivo, no contra un 8 que se quedo atras: este es el
        // contrato que destapo que `category_rows` reventaba con 500 al
        // guardarse, porque recorre TODOS los componentes registrados.
        $this->assertCount(count(StorefrontSections::COMPONENTS), $sections);
        $this->assertTrue($sections->every(fn ($section) => !$section->has_draft && $section->is_enabled));
    }

    public function test_popup_is_loaded_and_uses_the_correct_frequency_storage(): void
    {
        $project = $this->project();
        $popup = StorePopup::create([
            'project_id' => $project->id, 'title' => 'Campaña de prueba', 'description' => 'Oferta vigente',
            'delay_seconds' => 0, 'frequency' => 'session', 'show_desktop' => true,
            'show_mobile' => true, 'is_enabled' => true,
        ]);

        [, $data] = app(PublicController::class)->prepararCatalogo($project->fresh());
        $this->assertSame($popup->id, $data['popup']->id);
        $html = view('components.public-popup', ['popup' => $data['popup']])->render();
        $this->assertStringContainsString('id="bixo-store-popup"', $html);
        $this->assertStringContainsString("frequency==='session'?sessionStorage:localStorage", $html);
        $this->assertStringContainsString('Campaña de prueba', $html);
    }

    public function test_design_navigation_has_only_templates_and_visual_builder_tabs(): void
    {
        $project = $this->project();
        view()->share('activeProject', $project);
        request()->merge(['s' => 'constructor']);

        $html = app(SettingsController::class)->design(Request::create('/settings/design', 'GET', ['classic' => 1]))->render();
        $this->assertStringContainsString('?s=plantilla', $html);
        $this->assertStringContainsString('?s=constructor', $html);
        $this->assertSame(2, substr_count($html, 'data-primary-designer-tab='));
        $this->assertStringContainsString('data-primary-designer-tab="constructor"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('?s=constructor&amp;p='.$project->id, $html);
        $this->assertStringNotContainsString('?s=marca', $html);
        $this->assertStringNotContainsString('?s=portada', $html);
        $this->assertStringContainsString('id="constructor-inicio"', $html);
        $this->assertStringContainsString('id="constructor-checkout"', $html);

        request()->merge(['s' => 'templates']);
        $this->assertStringContainsString(
            'data-designer-section="plantilla"',
            app(SettingsController::class)->design(Request::create('/settings/design', 'GET', ['classic' => 1]))->render()
        );

        request()->merge(['s' => 'valor-desconocido']);
        $this->assertStringContainsString(
            'data-designer-section="constructor"',
            app(SettingsController::class)->design(Request::create('/settings/design', 'GET', ['classic' => 1]))->render()
        );
    }

    public function test_builder_navigation_keeps_scroll_inside_the_editor_and_returns_to_the_saved_section(): void
    {
        $project = $this->project();
        StorefrontSections::ensure($project);
        view()->share('activeProject', $project);
        request()->merge(['s' => 'constructor']);

        $html = app(SettingsController::class)->design(Request::create('/settings/design', 'GET', ['classic' => 1]))->render();
        $this->assertStringContainsString('data-design-scroll-container', $html);
        $this->assertStringContainsString("event.preventDefault()", $html);
        $this->assertStringContainsString("scroller.scrollTo", $html);
        $this->assertStringContainsString("resetOuterScroll", $html);
        $this->assertStringContainsString("overflow-anchor:none", $html);
        $this->assertStringNotContainsString("scrollIntoView({block:'start'})", $html);

        $response = app(StoreExperienceController::class)->saveHomeSection(
            Request::create('/settings/experience/home/hero', 'POST', $this->heroPayload('Portada estable', 'draft')),
            'hero'
        );

        $this->assertStringEndsWith(
            '/settings/design?s=constructor#home-section-hero',
            $response->getTargetUrl()
        );
    }
}
