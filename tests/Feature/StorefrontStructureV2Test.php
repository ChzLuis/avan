<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\StoreMenuItem;
use App\Models\StorePage;
use App\Models\User;
use App\Support\StorefrontNavigation;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontStructureV2Test extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug = 'tienda-v2'): array
    {
        $owner = User::factory()->create();
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
        $this->get(route('public.shop',[$project->slug,'category'=>$child->id]))
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
        $this->assertStringContainsString('category='.$child->id, StorefrontNavigation::resolveUrl($project,$item));
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
        ] as $url) $this->get($url)->assertOk()->assertSee('store-header',false)->assertSee('store-footer',false);
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
            'default' => 'public.catalog',
            'direct' => 'public.templates.direct',
            'ella' => 'public.templates.ella',
            'urban' => 'public.templates.urban',
            'boutique' => 'public.templates.boutique',
            'nordic' => 'public.templates.nordic',
            'flash' => 'public.templates.flash',
            'fresh' => 'public.templates.fresh',
            'porto' => 'public.templates.porto',
            'licoreria' => 'public.templates.licoreria',
            'farma' => 'public.templates.farma',
            'lavanderia' => 'public.templates.lavanderia',
            'ecommerce' => 'public.templates.ecommerce',
            'tecnologia' => 'public.templates.tecnologia',
            'computienda' => 'public.templates.computienda',
        ];

        foreach (array_keys(\App\Support\CatalogTemplates::all()) as $template) {
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
                ->assertViewIs('public.templates.direct');
        }

        $template = file_get_contents(resource_path('views/public/templates/direct.blade.php'));
        $this->assertSame(1, substr_count($template, "\$settings['hero_align']"));
        $this->assertStringContainsString("['left', 'center', 'right']", $template);
    }

    public function test_template_selector_updates_the_public_v2_theme_immediately(): void
    {
        [$owner, $project] = $this->project('selector-tema-v2');
        $project->settings()->updateOrCreate(['key' => 'header_active_color'], ['value' => '#123456']);

        foreach ([
            'ecommerce' => 'public.templates.ecommerce',
            'direct' => 'public.templates.direct',
        ] as $template => $expectedView) {
            $this->actingAs($owner)
                ->withSession(['active_project_id' => $project->id])
                ->postJson(route('settings.design.applyTemplate'), ['template' => $template])
                ->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('template', $template)
                ->assertJsonPath('theme.key', $template)
                ->assertJsonPath('public_url', route('public.catalog', $project->slug));

            $this->assertSame($template, $project->fresh()->setting('catalog_template'));
            $this->assertSame('#123456', $project->fresh()->setting('header_active_color'));
            $this->get(route('public.catalog', $project->slug))
                ->assertOk()
                ->assertViewIs($expectedView);
        }
    }

    public function test_production_templates_load_their_complete_original_views(): void
    {
        [, $project] = $this->project('vistas-base-v2');
        $views = [
            'computienda' => 'public.templates.computienda',
            'ecommerce' => 'public.templates.ecommerce',
            'direct' => 'public.templates.direct',
            'default' => 'public.catalog',
        ];

        foreach ($views as $template => $expectedView) {
            $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $template]);
            $this->get(route('public.catalog', $project->slug))
                ->assertOk()
                ->assertViewIs($expectedView);
            $this->assertSame(
                $expectedView,
                app(\App\Http\Controllers\PublicController::class)
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
            ->assertViewIs('public.templates.computienda')
            ->assertSee('catalog-filter-panel', false)
            ->assertSee('catalog-product-grid', false)
            ->assertSee('catalog-filter-drawer', false)
            ->assertSee('COMPUTIENDA_PRODUCTS', false)
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
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => 'ella']);

        $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('settings.design', ['s' => 'plantilla', 'applied' => 'ella']))
            ->assertOk()
            ->assertSee('Ella — Moda Minimalista aplicada')
            ->assertSee('Ver cambio en la tienda')
            ->assertDontSee('Pronto');
    }
}
