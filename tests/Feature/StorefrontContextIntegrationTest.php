<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicController;
use App\Models\Project;
use App\Models\StorePopup;
use App\Models\User;
use App\Storefront\StorefrontContext;
use App\Storefront\StorefrontContextBuilder;
use App\Support\CatalogTemplates;
use App\Support\StorefrontNavigation;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontContextIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug = 'contexto-a'): Project
    {
        $project = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Tienda '.$slug, 'slug' => $slug, 'is_active' => true]);
        StorefrontSections::ensure($project);
        StorefrontNavigation::ensure($project);
        return $project;
    }

    public function test_canonical_value_wins_and_alias_is_only_used_when_canonical_is_absent(): void
    {
        $project = $this->project();
        $project->settings()->createMany([
            ['key' => 'header_logo_url', 'value' => 'canonical.svg'],
            ['key' => 'logo_url', 'value' => 'legacy.svg'],
        ]);

        $builder = app(StorefrontContextBuilder::class);
        $canonical = $builder->forProject($project);
        $this->assertSame('canonical.svg', $canonical->setting('header_logo_url'));
        $this->assertNotContains('project_settings.logo_url->header_logo_url', $canonical->legacyFallbacksUsed());

        $project->settings()->where('key', 'header_logo_url')->delete();
        $aliased = $builder->forProject($project->fresh());
        $this->assertSame('legacy.svg', $aliased->setting('header_logo_url'));
        $this->assertContains('project_settings.logo_url->header_logo_url', $aliased->legacyFallbacksUsed());
    }

    public function test_template_and_support_defaults_apply_when_canonical_and_alias_are_missing(): void
    {
        $context = app(StorefrontContextBuilder::class)->forProject($this->project());

        $this->assertSame('default', $context->templateKey());
        $this->assertSame(CatalogTemplates::get('default')['settings']['primary_color'], $context->setting('primary_color'));
        $this->assertSame('#ffffff', $context->setting('header_bg_color'));
    }

    public function test_sections_navigation_pages_popup_and_template_come_from_canonical_sources(): void
    {
        $project = $this->project();
        $project->settings()->create(['key' => 'catalog_template', 'value' => 'ecommerce']);
        $project->storeSections()->where('component', 'hero')->update(['is_enabled' => true, 'content' => ['title' => 'Hero canónico']]);
        $project->storeSections()->where('component', 'benefits')->update(['is_enabled' => true, 'content' => ['title' => 'Benefits canónico']]);
        $project->storePages()->where('key', 'nosotros')->update(['title' => 'Nuestra historia']);
        $menu = $project->storeMenus()->where('location', 'primary')->firstOrFail();
        $menu->items()->where('destination_type', 'home')->update(['label' => 'Portada canónica']);
        $popup = StorePopup::create(['project_id' => $project->id, 'title' => 'Popup canónico', 'delay_seconds' => 0, 'frequency' => 'always', 'show_desktop' => true, 'show_mobile' => true, 'is_enabled' => true]);

        $context = app(StorefrontContextBuilder::class)->forProject($project->fresh());

        $this->assertSame('Hero canónico', $context->section('hero')->content['title']);
        $this->assertSame('Benefits canónico', $context->section('benefits')->content['title']);
        $this->assertSame('Portada canónica', $context->menu('header')->rootItems->firstWhere('destination_type', 'home')->label);
        $this->assertSame('Nuestra historia', $context->page('about')->title);
        $this->assertSame($popup->id, $context->popup()->id);
        $this->assertSame('ecommerce', $context->templateKey());
        $this->assertSame(CatalogTemplates::get('ecommerce')['label'], $context->template()['label']);
    }

    public function test_only_two_storefront_engines_are_officially_selectable(): void
    {
        $this->assertSame(['ecommerce', 'direct'], CatalogTemplates::supportedKeys());
    }

    public function test_all_official_templates_receive_the_same_context_and_derived_legacy_variables(): void
    {
        $project = $this->project();
        $project->settings()->create(['key' => 'primary_color', 'value' => '#102030']);

        foreach (CatalogTemplates::supportedKeys() as $template) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            [$view, $data] = app(PublicController::class)->prepararCatalogo($project->fresh());
            $this->assertSame(CatalogTemplates::get($template)['view'], $view);
            $this->assertInstanceOf(StorefrontContext::class, $data['storefrontContext']);
            $this->assertSame($data['storefrontContext']->globalSettings(), $data['settings']);
            $this->assertSame($data['storefrontContext']->sections(), $data['sections']);
            $this->assertSame($data['storefrontContext']->menu(), $data['storeMenu']);
            $this->assertSame('#102030', $data['settings']['primary_color']);
        }
    }

    public function test_projects_remain_isolated_across_consecutive_context_builds(): void
    {
        $first = $this->project('contexto-uno');
        $second = $this->project('contexto-dos');
        $first->settings()->create(['key' => 'hero_title', 'value' => 'Solo uno']);
        $second->settings()->create(['key' => 'hero_title', 'value' => 'Solo dos']);
        $first->storePages()->where('key', 'nosotros')->update(['title' => 'Página uno']);
        $second->storePages()->where('key', 'nosotros')->update(['title' => 'Página dos']);

        $builder = app(StorefrontContextBuilder::class);
        $one = $builder->forProject($first);
        $two = $builder->forProject($second);

        $this->assertSame($first->id, $one->project->id);
        $this->assertSame($second->id, $two->project->id);
        $this->assertSame('Solo uno', $one->setting('hero_title'));
        $this->assertSame('Solo dos', $two->setting('hero_title'));
        $this->assertSame('Página uno', $one->page('about')->title);
        $this->assertSame('Página dos', $two->page('about')->title);
    }

    public function test_adapted_blades_do_not_contain_database_reads(): void
    {
        // Superficies YA adaptadas: aqui una consulta a base desde la vista es
        // una regresion y debe fallar.
        $files = [
            'settings/qr.blade.php',
            'public/templates/ecommerce.blade.php', 'public/templates/direct.blade.php',
            'components/storefront-home-sections.blade.php',
        ];

        // PENDIENTES, nombrados a proposito para que no se pierdan:
        //  · `settings/design.blade.php` — 180 `$project->setting(` + 1
        //    `ProjectTemplate::`. Es la pantalla de Diseño RETIRADA; se elimina,
        //    no se adapta.
        //  · `public/templates/computienda.blade.php` — 1 `$project->settings(`.
        //  · `components/public-store-runtime.blade.php` — consulta productos y
        //    categorias (condicionalmente) al renderizar.
        // Migrar los dos ultimos toca TODA tienda publica y exige revision
        // visual, asi que va en su propio paso y no aqui.
        $patterns = [
            '$project->settings(', '$project->setting(', 'DB::', 'ProjectTemplate::',
            'Review::where(', '$project->products()', '$project->categories()', '$project->services()',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(resource_path('views/'.$file));
            foreach ($patterns as $pattern) $this->assertStringNotContainsString($pattern, $contents, $file.' contains '.$pattern);
        }
    }
}
