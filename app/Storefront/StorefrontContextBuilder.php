<?php

namespace App\Storefront;

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Review;
use App\Models\StoreMenu;
use App\Models\StoreMenuItem;
use App\Support\CatalogTemplates;
use App\Support\StorefrontNavigation;
use App\Support\StorefrontSections;
use App\Support\StorefrontTheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StorefrontContextBuilder
{
    private const ALIASES = [
        'header_logo_url' => ['logo_url'],
        'header_logo_height' => ['logo_height'],
        'quote_wa_msg' => ['whatsapp_msg'],
        'font_title' => ['font'],
        'font_body' => ['font'],
        'quote_whatsapp' => ['whatsapp'],
        'btn_cart_text' => ['cart_button_text'],
        'btn_checkout_text' => ['checkout_button_text'],
    ];

    public function forProject(Project $project, array $options = []): StorefrontContext
    {
        $preview = (bool) ($options['preview'] ?? false);
        $includeDisabled = (bool) ($options['include_disabled'] ?? false);
        $includeCatalog = (bool) ($options['include_catalog'] ?? false);
        $storeView = (string) ($options['store_view'] ?? 'home');

        $rawSettings = $project->relationLoaded('settings')
            ? $project->settings->pluck('value', 'key')->all()
            : $project->settings()->pluck('value', 'key')->all();

        $projectTemplates = ($options['include_project_templates'] ?? false)
            ? ProjectTemplate::where('project_id', $project->id)->orderBy('id')->get()
            : collect();
        $activeProjectTemplate = $projectTemplates->firstWhere('is_active', true)
            ?? ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->first();

        $templateKey = (string) $this->firstPresent($rawSettings, 'catalog_template', [], 'default');
        $template = CatalogTemplates::get($templateKey);
        if (!$template) {
            $templateKey = 'default';
            $template = CatalogTemplates::get('default') ?? [];
        }
        $template['key'] = $templateKey;

        [$settings, $legacyFallbacks] = $this->resolveSettings(
            $rawSettings,
            (array) ($activeProjectTemplate?->settings ?? []),
            array_merge(StorefrontNavigation::headerDefaults(), (array) ($template['settings'] ?? []), ['catalog_template' => $templateKey])
        );

        $sections = $this->sections($project, $settings, $preview, $includeDisabled);
        $pages = $project->storePages()->orderBy('key')->get()->keyBy('key');
        $menus = $this->menus($project, $includeDisabled, $pages, $legacyFallbacks);
        $popup = $this->popup($project, $preview, $includeDisabled);

        $catalog = ['headerSettings' => collect(StorefrontNavigation::headerDefaults())
            ->mapWithKeys(fn ($default, $key) => [$key => $settings[$key] ?? $default])->all()];
        $categories = collect();
        $products = collect();
        if ($includeCatalog) {
            [$categories, $products, $catalog] = $this->catalog($project, $catalog);
        }

        $urls = $this->urls($project);
        $theme = StorefrontTheme::resolve($settings);
        $capabilities = array_fill_keys((array) ($template['capabilities'] ?? []), true);

        // Read-only compatibility bridge for shared legacy components.
        $project->setRelation('storefrontProducts', $products);
        $project->setRelation('storefrontCategories', $categories);
        $project->setRelation('storefrontSectionsContext', $sections);
        $project->setRelation('storePages', $pages->values());

        return new StorefrontContext(
            $project, $settings, $template, $theme, $sections, $menus, $pages,
            $popup, $categories, $products, $urls, $capabilities,
            array_values(array_unique($legacyFallbacks)), $catalog, $projectTemplates,
            $storeView, $preview
        );
    }

    private function resolveSettings(array $canonical, array $legacyTemplate, array $defaults): array
    {
        $keys = array_unique(array_merge(array_keys($defaults), array_keys($legacyTemplate), array_keys($canonical), array_keys(self::ALIASES)));
        $resolved = [];
        $fallbacks = [];

        foreach ($keys as $key) {
            if ($this->present($canonical, $key)) {
                $resolved[$key] = $canonical[$key];
                continue;
            }
            foreach (self::ALIASES[$key] ?? [] as $alias) {
                if ($this->present($canonical, $alias)) {
                    $resolved[$key] = $canonical[$alias];
                    $fallbacks[] = "project_settings.{$alias}->{$key}";
                    continue 2;
                }
            }
            if ($this->present($legacyTemplate, $key)) {
                $resolved[$key] = $legacyTemplate[$key];
                $fallbacks[] = "project_templates.settings.{$key}";
                continue;
            }
            $resolved[$key] = $this->present($defaults, $key) ? $defaults[$key] : null;
        }

        return [$resolved, $fallbacks];
    }

    private function present(array $values, string $key): bool
    {
        return array_key_exists($key, $values) && $values[$key] !== null;
    }

    private function firstPresent(array $values, string $key, array $aliases, mixed $default): mixed
    {
        if ($this->present($values, $key)) return $values[$key];
        foreach ($aliases as $alias) if ($this->present($values, $alias)) return $values[$alias];
        return $default;
    }

    private function sections(Project $project, array $settings, bool $preview, bool $includeDisabled): Collection
    {
        $query = $project->storeSections()->where('page', 'home');
        if (!$preview && !$includeDisabled) {
            $query->where('is_enabled', true)
                ->where(fn ($q) => $q->whereNull('publish_from')->orWhere('publish_from', '<=', now()))
                ->where(fn ($q) => $q->whereNull('publish_until')->orWhere('publish_until', '>=', now()));
        }
        $sections = $query->orderBy('sort_order')->orderBy('id')->get()->groupBy('component')->map->first();

        if ($preview) {
            $sections = $sections->map(function ($source) {
                $section = clone $source;
                $section->content = $source->contentForPreview();
                $section->variant = $source->variantForPreview();
                $section->sort_order = $source->draft_sort_order ?? $source->sort_order;
                $section->is_enabled = $source->enabledForPreview();
                return $section;
            });
            if (!$includeDisabled) $sections = $sections->filter->is_enabled;
        }

        if ($includeDisabled) {
            $defaults = StorefrontSections::defaults($project, $settings);
            foreach ($defaults as $component => $definition) {
                if ($sections->has($component)) continue;
                $section = new \App\Models\StoreSection([
                    'project_id' => $project->id, 'page' => 'home', 'component' => $component,
                    'variant' => $definition['variant'], 'content' => $definition['content'],
                    'sort_order' => 999, 'is_enabled' => $definition['enabled'],
                ]);
                $sections->put($component, $section);
            }
        }

        return $sections->sortBy('sort_order')->values();
    }

    private function menus(Project $project, bool $includeDisabled, Collection $pages, array &$fallbacks): Collection
    {
        $query = $project->storeMenus();
        if (!$includeDisabled) $query->where('is_active', true);
        $menus = $query->with(['rootItems' => function ($query) use ($includeDisabled) {
            if (!$includeDisabled) $query->where('is_enabled', true);
            $query->with(['children' => function ($children) use ($includeDisabled) {
                if (!$includeDisabled) $children->where('is_enabled', true);
            }]);
        }])->orderBy('id')->get()->keyBy('location');

        if (!$menus->has('primary')) {
            $menus->put('primary', $this->fallbackMenu($project, $pages));
            $fallbacks[] = 'synthetic.primary_menu';
        }
        return $menus;
    }

    private function fallbackMenu(Project $project, Collection $pages): StoreMenu
    {
        $menu = new StoreMenu(['project_id' => $project->id, 'name' => 'Menú principal', 'location' => 'primary', 'is_active' => true]);
        $items = collect([
            ['label' => 'Inicio', 'destination_type' => 'home'],
            ['label' => 'Tienda', 'destination_type' => 'shop'],
            ['label' => 'Nosotros', 'destination_type' => 'about'],
            ['label' => 'Contacto', 'destination_type' => 'contact'],
            ['label' => 'Blog', 'destination_type' => 'blog'],
        ])->reject(fn ($item) => in_array($item['destination_type'], ['about', 'contact'], true)
            && !$pages->has($item['destination_type'] === 'about' ? 'nosotros' : 'contacto'))
            ->values()->map(function ($item, $index) use ($project, $menu) {
                $model = new StoreMenuItem($item + [
                    'project_id' => $project->id, 'sort_order' => ($index + 1) * 10,
                    'is_enabled' => true, 'show_desktop' => true, 'show_tablet' => true, 'show_mobile' => true,
                ]);
                $model->setRelation('children', collect());
                $model->setRelation('menu', $menu);
                return $model;
            });
        $menu->setRelation('rootItems', $items);
        return $menu;
    }

    private function popup(Project $project, bool $preview, bool $includeDisabled): ?\App\Models\StorePopup
    {
        $query = $project->storePopups();
        if (!$preview && !$includeDisabled) {
            $query->where('is_enabled', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
        }
        return $query->latest()->first();
    }

    private function catalog(Project $project, array $catalog): array
    {
        $allCategories = $project->categories()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $categoryMap = $allCategories->keyBy('id');
        $products = $project->products()->where('is_available', true)->with('mainImage')->orderBy('sort_order')->orderBy('id')->get();
        $services = $project->services()->where('is_available', true)->orderBy('sort_order')->orderBy('id')->get();

        foreach ($products as $product) $product->setRelation('category', $categoryMap->get($product->category_id));
        foreach ($allCategories as $category) {
            $category->setRelation('children', $allCategories->where('parent_id', $category->id)->values());
            $category->setRelation('products', $products->where('category_id', $category->id)->values());
            $category->setRelation('services', $services->where('category_id', $category->id)->values());
        }
        $categories = $allCategories->whereNull('parent_id')->values();

        foreach ($categories as $category) {
            $serviceModels = $category->services->concat($category->children->flatMap->services)->map(function ($service) {
                $product = new Product([
                    'name' => $service->name, 'description' => $service->description, 'price' => $service->price,
                    'category_id' => $service->category_id, 'is_available' => $service->is_available, 'sort_order' => $service->sort_order,
                ]);
                $product->id = 'svc-'.$service->id;
                $product->setRelation('mainImage', null);
                return $product;
            });
            $category->setRelation('products', $category->products->concat($serviceModels)->sortBy('sort_order')->values());
        }

        $productRatings = Review::where('project_id', $project->id)->where('is_approved', true)
            ->select('product_id', DB::raw('ROUND(AVG(rating),1) as avg_rating'), DB::raw('COUNT(*) as rating_count'))
            ->groupBy('product_id')->get()->keyBy('product_id');
        $testimonials = Review::where('project_id', $project->id)->where('is_approved', true)
            ->orderByDesc('rating')->take(3)->get();
        $newArrivals = $products->sortByDesc('created_at')->take(8)->values();
        $onSale = $products->filter(fn ($product) => $product->compare_price !== null && (float) $product->compare_price > (float) $product->price)->take(8)->values();
        $featured = $products->shuffle()->take(8)->values();
        if ($featured->isEmpty()) $featured = $categories->flatMap->products->take(8)->values();

        return [$categories, $products, $catalog + compact('newArrivals', 'onSale', 'featured', 'productRatings', 'testimonials')];
    }

    private function urls(Project $project): array
    {
        $base = StorefrontNavigation::publicUrl($project);
        return [
            'home' => $base,
            'shop' => rtrim($base, '/').'/tienda',
            'about' => rtrim($base, '/').'/nosotros',
            'contact' => rtrim($base, '/').'/contacto',
            'blog' => rtrim($base, '/').'/blog',
        ];
    }
}
