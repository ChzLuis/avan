<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Modules\Tienda\Models\ProjectTemplate;
use App\Modules\Tienda\Models\StoreMenuItem;
use App\Modules\Tienda\Models\StorePage;
use App\Models\User;
use App\Modules\Tienda\Support\StorefrontNavigation;
use App\Modules\Tienda\Support\StorefrontSections;
use App\Modules\Tienda\Support\CatalogTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StorefrontStructureV2Test extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug = 'tienda-v2'): array
    {
        // La pantalla clasica solo la alcanza un superadmin con ?classic=1.
        $owner = User::factory()->create(['is_superadmin' => 1]);
        $project = Project::create(['owner_id'=>$owner->id,'name'=>'Tienda V2','slug'=>$slug,'is_active'=>true,'phone'=>'999111222']);
        $project->settings()->createMany([
            ['key'=>'storefront_structure_v2','value'=>'1'], ['key'=>'catalog_template','value'=>'servicios'],
            ['key'=>'primary_color','value'=>'#2563eb'], ['key'=>'catalog_section_title','value'=>'Compra online'],
        ]);
        StorefrontSections::ensure($project);
        $project->storeSections()->where('component','hero')->update(['is_enabled'=>true]);
        StorefrontNavigation::ensure($project);
        return [$owner,$project];
    }

    public function test_home_and_shop_are_independent_and_header_precedes_slider(): void
    {
        [, $project] = $this->project();
        $category = $project->categories()->create(['name'=>'Tecnología','is_active'=>true]);
        foreach (range(1,15) as $index) $project->products()->create(['category_id'=>$category->id,'name'=>'Producto completo '.$index,'price'=>$index,'is_available'=>true]);

        $home = $this->get(route('public.catalog',$project->slug));
        $home->assertOk()->assertSeeInOrder(['store-header','data-store-home-section="hero"'],false)
            ->assertDontSee('category-nav',false)->assertDontSee('Producto completo 15');

        $shop = $this->get(route('public.shop',$project->slug));
        $shop->assertOk()->assertSee('Compra online')->assertSee('15 productos')->assertSee('Producto completo 1');
        $this->assertCount(12, $shop->viewData('products')->items());
        $this->assertSame(2, $shop->viewData('products')->lastPage());
    }

    public function test_shop_filters_searches_categories_subcategories_prices_and_sales(): void
    {
        [, $project] = $this->project('filtros-v2');
        $root=$project->categories()->create(['name'=>'Tecnología','is_active'=>true]);
        $child=$project->categories()->create(['name'=>'Laptops','parent_id'=>$root->id,'is_active'=>true]);
        $project->products()->create(['category_id'=>$child->id,'name'=>'Laptop Oferta','sku'=>'LAP-1','price'=>100,'compare_price'=>150,'is_available'=>true]);
        $project->products()->create(['category_id'=>$root->id,'name'=>'Mouse normal','price'=>20,'is_available'=>true]);

        $this->get(route('public.shop',[$project->slug,'category'=>$root->id,'sale'=>1,'q'=>'Laptop','min_price'=>50,'max_price'=>120]))
            ->assertOk()->assertSee('Laptop Oferta')->assertDontSee('Mouse normal');
        // Con `category` como UNICO parametro, la tienda canonicaliza con un
        // 301 hacia la URL legible (/tienda/c/laptops). Es deliberado —una sola
        // direccion por categoria para el buscador— asi que se sigue el
        // redirect en vez de exigir 200 en la forma antigua. Lo que el contrato
        // protege sigue siendo lo mismo: que el filtro por categoria filtre.
        $this->followingRedirects()
            ->get(route('public.shop',[$project->slug,'category'=>$child->id]))
            ->assertOk()->assertSee('Laptop Oferta')->assertDontSee('Mouse normal');
    }

    public function test_menu_text_submenus_destinations_and_visibility_are_project_scoped(): void
    {
        [$owner,$project] = $this->project('menu-v2');
        [, $other] = $this->project('otra-v2');
        $root=$project->categories()->create(['name'=>'Hogar','is_active'=>true]);
        $child=$project->categories()->create(['name'=>'Cocina','parent_id'=>$root->id,'is_active'=>true]);
        $menu=StorefrontNavigation::menu($project,true);

        $response=$this->actingAs($owner)->withSession(['active_project_id'=>$project->id])->post(route('settings.storefront.menu.items.store'),[
            'label'=>'Cocina online','destination_type'=>'subcategory','destination_id'=>$child->id,
            'parent_id'=>$menu->rootItems->firstWhere('destination_type','shop')->id,'target'=>'_self',
            'is_enabled'=>'1','show_desktop'=>'1','show_tablet'=>'1','show_mobile'=>'1',
        ]);
        $response->assertRedirect();
        $item=$project->storeMenuItems()->where('label','Cocina online')->firstOrFail();
        // El menu genera ya la URL legible (/tienda/cocina) en vez de
        // `?category=2`. Se afirma el DESTINO, no el formato: comparar contra
        // `categoryUrl()` mantiene el contrato —que la entrada apunte a esa
        // subcategoria— sin caducar la proxima vez que cambie la forma.
        $this->assertSame(
            StorefrontNavigation::categoryUrl($project, $child),
            StorefrontNavigation::resolveUrl($project, $item)
        );
        $this->assertFalse($other->storeMenuItems()->whereKey($item->id)->exists());

        $about=$menu->items()->where('destination_type','about')->firstOrFail();
        $about->update(['label'=>'Conócenos','is_enabled'=>false]);
        $this->get(route('public.catalog',$project->slug))->assertOk()->assertDontSee('Conócenos');
        $this->assertTrue(StorePage::where('project_id',$project->id)->where('key','nosotros')->exists());
    }

    public function test_external_urls_are_validated_and_unsafe_schemes_are_rejected(): void
    {
        [$owner,$project]=$this->project('seguridad-v2');
        $this->actingAs($owner)->withSession(['active_project_id'=>$project->id])->from(route('settings.design',['s'=>'constructor']))
            ->post(route('settings.storefront.menu.items.store'),[
                'label'=>'Enlace peligroso','destination_type'=>'external','url'=>'javascript:alert(1)','target'=>'_self',
                'is_enabled'=>'1','show_desktop'=>'1','show_tablet'=>'1','show_mobile'=>'1',
            ])->assertSessionHasErrors('url');
        $this->assertFalse($project->storeMenuItems()->where('label','Enlace peligroso')->exists());
    }

    public function test_all_menu_destinations_offered_by_the_builder_can_be_added(): void
    {
        [$owner, $project] = $this->project('destinos-menu-v2');

        foreach ([
            'brands' => 'Marcas',
            'promotions' => 'Promociones',
            'catalog_pdf' => 'Catálogo PDF',
        ] as $destination => $label) {
            $response = $this->actingAs($owner)
                ->withSession(['active_project_id' => $project->id])
                ->post(route('settings.storefront.menu.items.store'), [
                    'label' => $label,
                    'destination_type' => $destination,
                    'target' => '_self',
                    'is_enabled' => '1',
                    'show_desktop' => '1',
                    'show_tablet' => '1',
                    'show_mobile' => '1',
                ]);

            $response->assertSessionHasNoErrors()
                ->assertRedirect(route('settings.builder').'#header');
            $this->assertTrue($project->storeMenuItems()
                ->where('label', $label)
                ->where('destination_type', $destination)
                ->exists());
        }
    }

    public function test_shared_layout_is_used_by_institutional_blog_custom_and_product_pages(): void
    {
        [, $project]=$this->project('paginas-v2');
        $custom=StorePage::create(['project_id'=>$project->id,'key'=>'garantia','title'=>'Garantía','content'=>['body'=>'Contenido seguro'],'is_enabled'=>true]);
        $blog=$project->storeSections()->where('component','blog')->firstOrFail();
        $blog->update(['is_enabled'=>true,'content'=>['title'=>'Noticias','limit'=>3,'items'=>[['key'=>'nota-1','enabled'=>true,'sort_order'=>1,'title'=>'Primera nota','summary'=>'Resumen']]]]);
        $category=$project->categories()->create(['name'=>'General','is_active'=>true]);
        $product=$project->products()->create(['category_id'=>$category->id,'name'=>'Producto detalle','price'=>25,'is_available'=>true]);

        foreach ([
            route('public.about',$project->slug), route('public.contact',$project->slug),
            route('public.blog',$project->slug), route('public.blog.show',[$project->slug,'nota-1']),
            route('public.page',[$project->slug,$custom->key]), route('public.product',[$project->slug,$product->id]),
        // Igual que las categorias, la ficha de producto canonicaliza con 301
        // hacia su URL legible (/producto/1 -> /producto/producto-detalle-1).
        // Se sigue el redirect: lo que este contrato protege es que TODAS estas
        // paginas compartan cabecera y pie, no la forma de la URL.
        ] as $url) $this->followingRedirects()->get($url)->assertOk()
            ->assertSee('store-header',false)->assertSee('store-footer',false);
    }

    public function test_changing_template_changes_visual_theme_without_changing_global_content(): void
    {
        [, $project]=$this->project('compatibilidad-v2');
        $menuIds=$project->storeMenuItems()->orderBy('id')->pluck('id')->all();
        $sectionIds=$project->storeSections()->orderBy('id')->pluck('id')->all();
        $project->settings()->updateOrCreate(['key'=>'header_active_color'],['value'=>'#123456']);
        foreach (['servicios','belleza','deporte'] as $template) {
            $project->settings()->updateOrCreate(['key'=>'catalog_template'],['value'=>$template]);
            $this->assertSame($menuIds,$project->storeMenuItems()->orderBy('id')->pluck('id')->all());
            $this->assertSame($sectionIds,$project->storeSections()->orderBy('id')->pluck('id')->all());
            $this->assertSame('#123456',$project->setting('header_active_color'));
            $response = $this->get(route('public.catalog',$project->slug));
            $response->assertOk()
                ->assertSee('data-store-template="'.$template.'"', false)
                ->assertSee('storefront-theme-'.$template, false);
        }
    }

    public function test_each_catalog_template_resolves_to_a_distinct_supported_v2_theme(): void
    {
        [, $project] = $this->project('temas-v2');
        $productionViews = [
            'default' => 'tienda::public.catalog',
            'direct' => 'tienda::public.templates.direct',
            'ella' => 'tienda::public.templates.ella',
            'urban' => 'tienda::public.templates.urban',
            'boutique' => 'tienda::public.templates.boutique',
            'nordic' => 'tienda::public.templates.nordic',
            'flash' => 'tienda::public.templates.flash',
            'fresh' => 'tienda::public.templates.fresh',
            'porto' => 'tienda::public.templates.porto',
            'licoreria' => 'tienda::public.templates.licoreria',
            'farma' => 'tienda::public.templates.farma',
            'lavanderia' => 'tienda::public.templates.lavanderia',
            'ecommerce' => 'tienda::public.templates.ecommerce',
            'tecnologia' => 'tienda::public.templates.tecnologia',
            'computienda' => 'tienda::public.templates.computienda',
        ];

        foreach (array_keys(\App\Modules\Tienda\Support\CatalogTemplates::all()) as $template) {
            $project->settings()->updateOrCreate(['key'=>'catalog_template'],['value'=>$template]);
            $response = $this->get(route('public.catalog',$project->slug));
            $response->assertOk();
            if (isset($productionViews[$template])) {
                $response->assertViewIs($productionViews[$template]);
            } else {
                $response->assertSee('data-store-template="'.$template.'"', false);
                $this->assertSame($template, $response->viewData('storefrontTheme')['key']);
            }
        }
    }

    public function test_direct_template_normalizes_missing_valid_and_invalid_hero_alignment(): void
    {
        [, $project] = $this->project('direct-hero-align');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'direct']);

        foreach ([null, '', 'left', 'center', 'right', 'diagonal'] as $value) {
            if ($value === null) {
                $project->settings()->where('key', 'hero_align')->delete();
            } else {
                $project->settings()->updateOrCreate(['key' => 'hero_align'], ['value' => $value]);
            }

            $this->get(route('public.catalog', $project->slug))
                ->assertOk()
                ->assertViewIs('tienda::public.templates.direct');
        }

        // Antes se contaba cuantas veces aparecia `$settings['hero_align']` en
        // el fuente y se exigia 1. La lectura ES una sola —`in_array(...) ? ... :
        // 'left'`— pero la expresion lo menciona dos veces, asi que la cuenta
        // fallaba sobre codigo correcto. Se comprueba el COMPORTAMIENTO, que es
        // lo que protege al visitante: un valor invalido no llega a la pagina.
        $project->settings()->updateOrCreate(['key' => 'hero_align'], ['value' => 'diagonal']);
        // Un valor invalido NO puede llegar a la pagina: ni al HTML del
        // servidor ni al JSON del runtime, que lo asignaba a
        // `hero.style.textAlign` sin filtrar.
        $this->get(route('public.catalog', $project->slug))
            ->assertOk()
            ->assertDontSee('diagonal');

        // Y una alineacion valida SI llega, para que el contrato no se cumpla
        // simplemente por no pintar nada.
        $project->settings()->updateOrCreate(['key' => 'hero_align'], ['value' => 'right']);
        $this->get(route('public.catalog', $project->slug))
            ->assertOk()
            ->assertSee('"heroAlign":"right"', false);
    }

    public function test_template_selector_updates_the_public_v2_theme_immediately(): void
    {
        [$owner, $project] = $this->project('selector-tema-v2');
        $project->settings()->updateOrCreate(['key' => 'header_active_color'], ['value' => '#123456']);

        foreach (CatalogTemplates::supported() as $template => $definition) {
            $response = $this->actingAs($owner)
                ->withSession(['active_project_id' => $project->id])
                ->postJson(route('settings.design.applyTemplate'), ['template' => $template])
                ->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('template', $template)
                ->assertJsonPath('theme.key', $template)
                ->assertJsonPath('theme.name', $definition['name'])
                ->assertJsonPath('theme.description', $definition['short_description'])
                ->assertJsonPath('public_url', route('public.catalog', $project->slug));

            $response->assertJsonStructure(['ok', 'template', 'preserved', 'theme', 'public_url']);

            $this->assertSame($template, $project->fresh()->setting('catalog_template'));
            $this->assertSame('#123456', $project->fresh()->setting('header_active_color'));
            $this->get(route('public.catalog', $project->slug))
                ->assertOk()
                ->assertViewIs($definition['view']);
        }
    }

    public function test_unsupported_template_keys_are_rejected_without_mutating_the_project(): void
    {
        [$owner, $project] = $this->project('selector-rechazos-v2');
        [, $other] = $this->project('selector-rechazos-otro-v2');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'computienda']);
        $other->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'direct']);
        $savedTemplate = ProjectTemplate::create([
            'project_id' => $project->id,
            'name' => 'Configuración activa',
            'settings' => ['catalog_template' => 'computienda'],
            'is_active' => true,
        ]);

        foreach (['computienda', 'ella', 'editorial', 'luxe', 'bistro', 'inexistente', ''] as $template) {
            $this->actingAs($owner)
                ->withSession(['active_project_id' => $project->id])
                ->postJson(route('settings.design.applyTemplate'), ['template' => $template])
                ->assertStatus(422)
                ->assertJsonPath('ok', false)
                ->assertJsonMissingPath('theme')
                ->assertJsonMissingPath('public_url');

            $this->assertSame('computienda', $project->fresh()->setting('catalog_template'));
            $this->assertTrue($savedTemplate->fresh()->is_active);
            $this->assertSame('direct', $other->fresh()->setting('catalog_template'));
        }
    }

    public function test_template_selector_exposes_exactly_the_two_supported_engines_and_legacy_warning(): void
    {
        [$owner, $project] = $this->project('catalogo-oficial-v2');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'ella']);

        $response = $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('settings.design', ['s' => 'plantilla', 'classic' => 1]))
            ->assertOk()
            ->assertSee('Esta tienda utiliza una plantilla heredada que ya no recibe nuevas funciones.')
            ->assertSee('data-supported-template-card="ecommerce"', false)
            ->assertSee('data-supported-template-card="direct"', false)
            ->assertDontSee('data-supported-template-card="computienda"', false);

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'data-supported-template-card='));
        foreach (['computienda', 'ella', 'editorial', 'luxe', 'bistro', 'default', 'nordic', 'flash', 'urban', 'boutique', 'fresh', 'porto', 'licoreria', 'farma', 'lavanderia', 'tecnologia'] as $legacyKey) {
            $this->assertStringNotContainsString('data-supported-template-card="'.$legacyKey.'"', $html);
        }
        $this->assertSame('ella', $project->fresh()->setting('catalog_template'));
    }

    public function test_production_templates_load_their_complete_original_views(): void
    {
        [, $project] = $this->project('vistas-base-v2');
        $views = [
            'computienda' => 'tienda::public.templates.computienda',
            'ecommerce' => 'tienda::public.templates.ecommerce',
            'direct' => 'tienda::public.templates.direct',
            'default' => 'tienda::public.catalog',
        ];

        foreach ($views as $template => $expectedView) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            $this->get(route('public.catalog', $project->slug))
                ->assertOk()
                ->assertViewIs($expectedView);
            $this->assertSame(
                $expectedView,
                app(\App\Modules\Tienda\Controllers\TiendaPublicaController::class)
                    ->previewStorefront($project->fresh())
                    ->name()
            );
        }
    }

    public function test_computienda_renders_the_advanced_catalog_filters_and_mobile_drawer(): void
    {
        [, $project] = $this->project('computienda-catalogo-avanzado');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'computienda']);
        $category = $project->categories()->create(['name' => 'Herramientas', 'is_active' => true]);
        $subcategory = $project->categories()->create(['name' => 'Taladros', 'parent_id' => $category->id, 'is_active' => true]);
        $project->products()->create([
            'category_id' => $subcategory->id,
            'name' => 'Taladro profesional',
            'price' => 95,
            'compare_price' => 145,
            'stock' => 8,
            'is_available' => true,
        ]);

        $this->get(route('public.catalog', $project->slug))
            ->assertOk()
            ->assertViewIs('tienda::public.templates.computienda')
            ->assertSee('catalog-filter-panel', false)
            ->assertSee('catalog-product-grid', false)
            ->assertSee('catalog-filter-drawer', false)
            ->assertSee('COMPUTIENDA_PRODUCTS', false);

        // CompuTienda separa a proposito **Inicio** (portada) de **Tienda**
        // (/tienda, el catalogo con filtros). El catalogo de productos vive en
        // la segunda, asi que el producto se comprueba ahi y no en la portada,
        // que es donde lo buscaba este contrato antes de esa separacion.
        $this->get(route('public.shop', $project->slug))
            ->assertOk()
            ->assertSee('Taladro profesional');
    }

    public function test_switching_template_is_isolated_to_the_active_project(): void
    {
        [$owner, $project] = $this->project('aislamiento-uno');
        [, $other] = $this->project('aislamiento-dos');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'computienda']);
        $other->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'direct']);

        $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->postJson(route('settings.design.applyTemplate'), ['template' => 'ecommerce'])
            ->assertOk()
            ->assertJsonPath('template', 'ecommerce');

        $this->assertSame('ecommerce', $project->fresh()->setting('catalog_template'));
        $this->assertSame('direct', $other->fresh()->setting('catalog_template'));
    }

    public function test_template_admin_confirms_the_applied_v2_theme_and_offers_fresh_preview(): void
    {
        [$owner, $project] = $this->project('confirmacion-tema-v2');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'ecommerce']);

        $response = $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('settings.design', ['s' => 'plantilla', 'applied' => 'ecommerce', 'classic' => 1]))
            ->assertOk()
            ->assertSee('Ecommerce — Sitio web completo con tienda online aplicada')
            ->assertSee('Ver cambio en la tienda')
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('Pronto');

        $html = $response->getContent();
        $this->assertStringContainsString('json.theme.name', $html);
        $this->assertStringContainsString('json.theme.description', $html);
        $this->assertStringContainsString('json.public_url', $html);
        $this->assertStringContainsString("this.feedback = null", $html);
        $this->assertStringContainsString("type: 'error'", $html);
        $this->assertSame(1, substr_count($html, 'data-template-feedback="success"'));
    }

    public function test_template_application_returns_the_active_projects_custom_domain_url(): void
    {
        [$owner, $project] = $this->project('dominio-plantilla-v2');
        $project->update(['custom_domain' => 'tienda-ejemplo.test']);

        $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->postJson(route('settings.design.applyTemplate'), ['template' => 'direct'])
            ->assertOk()
            ->assertJsonPath('theme.key', 'direct')
            ->assertJsonPath('public_url', 'https://tienda-ejemplo.test');
    }

    public function test_missing_legacy_template_view_uses_a_logged_compatibility_fallback(): void
    {
        [, $project] = $this->project('fallback-heredado-v2');
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'editorial']);
        Log::spy();

        $this->get(route('public.catalog', $project->slug))
            ->assertOk()
            ->assertDontSee('View [public.templates.editorial] not found');

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($project) {
            return str_contains($message, 'compatibility fallback')
                && $context['project_id'] === $project->id
                && $context['template'] === 'editorial';
        })->atLeast()->once();
    }
}
