<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\StoreMenu;
use App\Models\StorePage;
use App\Models\StorePopup;
use App\Models\StoreSection;
use Illuminate\Support\Collection;

/**
 * Immutable, request-scoped read model used by the Store Builder and storefront.
 *
 * It deliberately contains no persistence methods: write flows continue to live
 * in their existing controllers during phase 1A.
 */
final class StorefrontContext
{
    public function __construct(
        public readonly Project $project,
        private readonly array $settings,
        private readonly array $template,
        private readonly array $theme,
        private readonly Collection $sectionCollection,
        private readonly Collection $menuCollection,
        private readonly Collection $pageCollection,
        private readonly ?StorePopup $activePopup,
        private readonly Collection $categoryCollection,
        private readonly Collection $productCollection,
        private readonly array $urls,
        private readonly array $capabilityMap,
        private readonly array $legacyFallbacks,
        private readonly array $catalogData,
        private readonly Collection $templateCollection,
        public readonly string $storeView = 'home',
        public readonly bool $preview = false,
    ) {
    }

    public function setting(string $key, mixed $fallback = null): mixed
    {
        return array_key_exists($key, $this->settings) ? $this->settings[$key] : $fallback;
    }

    public function globalSettings(): array { return $this->settings; }
    public function template(): array { return $this->template; }
    public function templateKey(): string { return (string) ($this->template['key'] ?? 'default'); }
    public function theme(): array { return $this->theme; }
    public function sections(): Collection { return $this->sectionCollection; }
    public function section(string $component): ?StoreSection { return $this->sectionCollection->firstWhere('component', $component); }
    public function menus(): Collection { return $this->menuCollection; }
    public function menu(string $location = 'primary'): ?StoreMenu { return $this->menuCollection->get($location === 'header' ? 'primary' : $location); }
    public function pages(): Collection { return $this->pageCollection; }

    public function page(string $key): ?StorePage
    {
        $key = match ($key) { 'about' => 'nosotros', 'contact' => 'contacto', default => $key };
        return $this->pageCollection->get($key);
    }

    public function popup(): ?StorePopup { return $this->activePopup; }
    public function categories(): Collection { return $this->categoryCollection; }
    public function products(): Collection { return $this->productCollection; }
    public function publicUrl(string $name = 'home'): ?string { return $this->urls[$name] ?? null; }
    public function publicUrls(): array { return $this->urls; }
    public function capabilities(): array { return $this->capabilityMap; }
    public function legacyFallbacksUsed(): array { return $this->legacyFallbacks; }
    public function projectTemplates(): Collection { return $this->templateCollection; }

    public function catalog(string $key, mixed $fallback = null): mixed
    {
        return array_key_exists($key, $this->catalogData) ? $this->catalogData[$key] : $fallback;
    }

    /** Variables kept for existing Blade contracts while they migrate to context access. */
    public function toViewData(): array
    {
        return [
            'storefrontContext' => $this,
            'project' => $this->project,
            'settings' => $this->globalSettings(),
            'sections' => $this->sections(),
            'popup' => $this->popup(),
            'storeMenu' => $this->menu(),
            'headerSettings' => $this->catalog('headerSettings', []),
            'storefrontTheme' => $this->theme(),
            'previewMode' => $this->preview,
            'storeView' => $this->storeView,
            'categories' => $this->categories(),
            'newArrivals' => $this->catalog('newArrivals', collect()),
            'onSale' => $this->catalog('onSale', collect()),
            'featured' => $this->catalog('featured', collect()),
            'productRatings' => $this->catalog('productRatings', collect()),
            'testimonials' => $this->catalog('testimonials', collect()),
            'aboutPage' => $this->page('nosotros'),
        ];
    }
}
