<?php

namespace Tests\Feature;

use App\Modules\Tienda\Controllers\TiendaPublicaController;
use App\Http\Controllers\SettingsController;
use App\Modules\Tienda\Controllers\StoreExperienceController;
use App\Models\Project;
use App\Modules\Tienda\Models\StorePopup;
use App\Models\User;
use App\Modules\Tienda\Support\CatalogTemplates;
use App\Modules\Tienda\Support\StorefrontSections;
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
        // Llamar al controlador directamente se salta ShareErrorsFromSession,
        // que es quien comparte `$errors` con las vistas en una peticion real.
        // Se comparte a mano para que la vista se pueda renderizar aqui.
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);
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

        [, $publicData] = app(TiendaPublicaController::class)->prepararCatalogo($project->fresh());
        [, $previewData] = app(TiendaPublicaController::class)->prepararCatalogo($project->fresh(), true);
        $this->assertSame($publishedTitle, data_get($publicData['sections']->firstWhere('component', 'hero')->content, 'single.title'));
        $this->assertSame('Portada en borrador', data_get($previewData['sections']->firstWhere('component', 'hero')->content, 'single.title'));

        $publishRequest = Request::create('/settings/experience/home/hero', 'POST', $this->heroPayload('Portada publicada', 'publish'));
        app(StoreExperienceController::class)->saveHomeSection($publishRequest, 'hero');
        $hero->refresh();
        $this->assertFalse($hero->has_draft);
        $this->assertSame('Portada publicada', data_get($hero->content, 'single.title'));

        foreach (['direct', 'computienda', 'ecommerce'] as $template) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            [$view, $data] = app(TiendaPublicaController::class)->prepararCatalogo($project->fresh());
            $this->assertStringContainsString($template === 'direct' ? 'direct' : $template, $view);
            $this->assertSame('Portada publicada', data_get($data['sections']->firstWhere('component', 'hero')->content, 'single.title'));
        }
    }

    public function test_admin_builder_and_public_store_render_without_errors(): void
    {
        $project = $this->project();
        $adminView = app(SettingsController::class)->design(Request::create('/settings/design', 'GET', ['classic' => 1]));
        $this->assertCount(count(StorefrontSections::COMPONENTS), $adminView->getData()['homeSections']);
        $this->assertTrue(
            $adminView->getData()['homeSections']->every(fn ($section) => $section->project_id === $project->id)
        );
        $adminHtml = view('settings.partials.home-builder', $adminView->getData())->render();
        $this->assertStringContainsString('CONSTRUCTOR VISUAL', $adminHtml);
        $this->assertStringContainsString('Productos con descuento', $adminHtml);
        $this->assertStringContainsString('Guardar y publicar', $adminHtml);

        $project->storeSections()->whereIn('component', ['hero', 'benefits', 'featured_categories'])->update(['is_enabled' => true]);
        $project->categories()->create(['name' => 'Categoría principal', 'is_active' => true, 'sort_order' => 1]);
        [, $data] = app(TiendaPublicaController::class)->prepararCatalogo($project->fresh());
        $publicHtml = view('tienda::components.storefront-home-sections', $data)->render();
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

        // Solo las plantillas SOPORTADAS —las que un negocio puede elegir de
        // verdad—. `all()` incluye claves del catalogo historico sin Blade
        // propio o sin el hero del constructor, y exigirles el marcador era
        // pedirselo a codigo que ninguna tienda puede servir.
        foreach (array_keys(CatalogTemplates::supported()) as $template) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            [$view, $data] = app(TiendaPublicaController::class)->prepararCatalogo($project->fresh());
            $html = view($view, $data)->render();

            // Hay DOS mecanismos legitimos para exponer una seccion al
            // constructor, y el contrato solo conocia uno:
            //   · `data-store-home-section` — componente compartido
            //     (`storefront-home-sections`), que usa computienda.
            //   · `data-store-native-section` — secciones propias de la
            //     plantilla, que es como lo hace ecommerce.
            // Lo que importa es que el hero SEA controlable, no con que
            // vocabulario se marque. Nota: mirar el .blade de la plantilla no
            // basta —`direct` no tiene marcadores propios pero SI los emite,
            // porque los pone el runtime compartido que incluye—. Por eso se
            // comprueba sobre el HTML renderizado y no sobre el fuente.
            $marcado = str_contains($html, 'data-store-home-section="hero"')
                || str_contains($html, 'data-store-native-section="hero"');

            $this->assertTrue($marcado, "la plantilla '{$template}' debe exponer su hero al constructor");
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

        [, $data] = app(TiendaPublicaController::class)->prepararCatalogo($project->fresh());
        $this->assertSame($popup->id, $data['popup']->id);
        $html = view('tienda::components.public-popup', ['popup' => $data['popup']])->render();
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
        // El enlace ya no arrastra `&p={id}`: el proyecto activo va por sesion.
        // Se comprueba lo que importa —que la pestaña del Constructor exista y
        // apunte a su seccion— y no la forma exacta de la query, que cambia
        // cada vez que se toca como se propaga el proyecto.
        $this->assertStringContainsString('?s=constructor', $html);
        $this->assertStringNotContainsString('?s=marca', $html);
        $this->assertStringNotContainsString('?s=portada', $html);
        $this->assertStringContainsString('id="constructor-inicio"', $html);
        $this->assertStringContainsString('id="constructor-checkout"', $html);

        // La seccion se llama `plantilla` (antes `templates`): el controlador
        // solo la reconoce con el nombre actual y cualquier otro valor cae al
        // Constructor, que es justo lo que comprueba el bloque siguiente.
        request()->merge(['s' => 'plantilla']);
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

        // Tras guardar se vuelve al CONSTRUCTOR, no a la pantalla de Diseño
        // retirada, y con el ancla de la seccion para no perder la posicion.
        $this->assertStringEndsWith(
            '/settings/builder#home-section-hero',
            $response->getTargetUrl()
        );
    }
}
