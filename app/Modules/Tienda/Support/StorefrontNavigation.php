<?php

namespace App\Modules\Tienda\Support;

use App\Models\Project;
use App\Modules\Tienda\Models\StoreMenu;
use App\Modules\Tienda\Models\StoreMenuItem;
use App\Modules\Tienda\Models\StorePage;
use Illuminate\Support\Collection;

class StorefrontNavigation
{
    public static function ensure(Project $project): StoreMenu
    {
        StorePage::firstOrCreate(
            ['project_id' => $project->id, 'key' => 'nosotros'],
            ['title' => 'Nosotros', 'content' => ['body' => '', 'show_history' => true, 'show_mission' => true, 'show_vision' => true, 'show_values' => true, 'show_team' => true], 'is_enabled' => true]
        );
        StorePage::firstOrCreate(
            ['project_id' => $project->id, 'key' => 'contacto'],
            ['title' => 'Contacto', 'content' => ['body' => 'Cuéntanos cómo podemos ayudarte.', 'require_phone' => false, 'require_email' => false, 'confirmation_message' => 'Recibimos tu mensaje. Te responderemos pronto.'], 'is_enabled' => true]
        );
        $menu = StoreMenu::firstOrCreate(
            ['project_id' => $project->id, 'location' => 'primary'],
            ['name' => 'Menú principal', 'is_active' => true]
        );

        if (!$menu->items()->exists()) {
            foreach ([
                ['label' => 'Inicio', 'destination_type' => 'home'],
                ['label' => 'Tienda', 'destination_type' => 'shop'],
                ['label' => 'Nosotros', 'destination_type' => 'about'],
                ['label' => 'Contacto', 'destination_type' => 'contact'],
                ['label' => 'Blog', 'destination_type' => 'blog'],
            ] as $index => $item) {
                $menu->items()->create($item + [
                    'project_id' => $project->id,
                    'sort_order' => ($index + 1) * 10,
                    'is_enabled' => true,
                    'show_desktop' => true,
                    'show_tablet' => true,
                    'show_mobile' => true,
                ]);
            }
        }

        return $menu->fresh();
    }

    public static function menu(Project $project, bool $includeDisabled = false): ?StoreMenu
    {
        $query = StoreMenu::where('project_id', $project->id)->where('location', 'primary');
        if (!$includeDisabled) $query->where('is_active', true);

        return $query->with(['rootItems' => function ($query) use ($includeDisabled) {
            if (!$includeDisabled) $query->where('is_enabled', true);
            $query->with(['children' => function ($children) use ($includeDisabled) {
                if (!$includeDisabled) $children->where('is_enabled', true);
            }]);
        }])->first();
    }

    public static function headerSettings(Project $project): array
    {
        $saved = $project->settings()->whereIn('key', array_keys(self::headerDefaults()))->pluck('value', 'key')->all();
        return array_merge(self::headerDefaults(), $saved);
    }

    public static function headerDefaults(): array
    {
        return [
            'header_bg_color' => '#ffffff', 'header_text_color' => '#0f172a',
            'header_hover_color' => '#2563eb', 'header_active_color' => '#1d4ed8',
            'header_font' => 'Inter', 'header_font_size' => '14', 'header_height' => '76',
            'header_sticky' => '1', 'header_show_search' => '1', 'header_show_contact' => '1',
            'header_show_cart' => '1', 'header_logo_url' => '', 'header_logo_height' => '44',
            'header_mobile_style' => 'drawer', 'header_tablet_style' => 'drawer',
        ];
    }

    /**
     * URL pública canónica de la tienda para respuestas administrativas y
     * enlaces de vista previa. Prioriza el dominio propio del proyecto.
     */
    public static function publicUrl(Project $project): string
    {
        $customDomain = trim((string) $project->custom_domain);

        return $customDomain !== ''
            ? 'https://' . $customDomain
            : route('public.catalog', $project->slug);
    }

    /** Inicio y tienda respetando el host: en dominio propio, sin el slug interno. */
    public static function homeUrl(Project $project): string
    {
        return self::enDominioPropio($project) ? '/' : url('/'.$project->slug);
    }

    public static function shopUrl(Project $project): string
    {
        return self::enDominioPropio($project) ? '/tienda' : url('/'.$project->slug.'/tienda');
    }

    public static function profileUrl(Project $project, string $slugPerfil): string
    {
        return self::shopUrl($project).'/'.$slugPerfil;
    }

    /**
     * URL de una categoría, legible: /tienda/computadoras en vez de
     * /tienda?category=394.
     *
     * Acepta el id o el propio modelo. Si la categoría todavía no tiene slug
     * —una recién creada por un flujo que no pase por el modelo— cae a la forma
     * antigua, que sigue funcionando. Nunca devuelve un enlace roto.
     */
    public static function categoryUrl(Project $project, $categoria): string
    {
        $base = self::shopUrl($project);

        $cat = is_object($categoria)
            ? $categoria
            : \App\Modules\Catalogo\Models\Category::where('project_id', $project->id)->find($categoria);

        // Sin prefijo `c/`: perfiles y categorías comparten segmento y el
        // controlador resuelve cuál es. Las URLs con `c/` siguen sirviéndose
        // por compatibilidad con lo que ya se haya compartido.
        return ($cat && filled($cat->slug))
            ? $base.'/'.$cat->slug
            : $base.'?category='.(is_object($categoria) ? $categoria->id : $categoria);
    }

    private static function enDominioPropio(Project $project): bool
    {
        $dominio = trim((string) $project->custom_domain, '/');

        return $dominio !== '' && request()->getHost() === $dominio;
    }

    public static function resolveUrl(Project $project, StoreMenuItem $item): string
    {
        // En dominio custom (ej. tecsist.net) las URLs van SIN el slug del proyecto:
        // /tienda, /nosotros, etc. Fuera del dominio custom, usan /{slug}/...
        $onCustomDomain = $project->custom_domain
            && request()->getHost() === $project->custom_domain;

        $base = $onCustomDomain ? '' : '/' . $project->slug;
        $home = $onCustomDomain ? '/' : '/' . $project->slug;

        $shopUrl = $base . '/tienda';
        // Enlaces de menú a categoría: forma legible (/tienda/c/computadoras).
        // `categoryUrl()` cae sola a `?category={id}` si esa categoría todavía
        // no tuviera slug, así que nunca devuelve un enlace roto.
        $categoryUrl = fn ($id) => self::categoryUrl($project, $id);

        return match ($item->destination_type) {
            'home' => $home,
            'shop', 'products' => $shopUrl,
            'category', 'subcategory' => $categoryUrl($item->destination_id),
            'brands' => $base . '/marcas',
            'promotions' => $base . '/promociones',
            'catalog_pdf' => $base . '/catalogo',
            'about' => $base . '/nosotros',
            'contact' => $base . '/contacto',
            'blog' => $base . '/blog',
            'page' => self::pageUrl($project, $item),
            'external' => self::safeExternalUrl($item->url),
            default => $home,
        };
    }

    public static function isActive(StoreMenuItem $item, string $routeName, ?int $categoryId = null): bool
    {
        return match ($item->destination_type) {
            'home' => $routeName === 'public.catalog',
            'brands' => in_array($routeName, ['public.brands', 'public.brand'], true),
            'promotions' => $routeName === 'public.promotions',
            'catalog_pdf' => $routeName === 'public.catalog_page',
            'shop', 'products' => $routeName === 'public.shop' && !$categoryId,
            'category', 'subcategory' => $routeName === 'public.shop' && (int) $item->destination_id === (int) $categoryId,
            'about' => $routeName === 'public.about', 'contact' => $routeName === 'public.contact',
            'blog' => str_starts_with($routeName, 'public.blog'), 'page' => $routeName === 'public.page',
            default => false,
        };
    }

    public static function deviceClasses(StoreMenuItem $item): string
    {
        return collect([
            !$item->show_desktop ? 'store-nav-hide-desktop' : null,
            !$item->show_tablet ? 'store-nav-hide-tablet' : null,
            !$item->show_mobile ? 'store-nav-hide-mobile' : null,
        ])->filter()->implode(' ');
    }

    private static function pageUrl(Project $project, StoreMenuItem $item): string
    {
        static $pageKeys = [];
        $pageKeys[$project->id] ??= $project->relationLoaded('storePages')
            ? $project->storePages->pluck('key', 'id')->all()
            : $project->storePages()->pluck('key', 'id')->all();
        $key = $pageKeys[$project->id][$item->destination_id] ?? null;
        return $key ? route('public.page', [$project->slug, $key]) : route('public.catalog', $project->slug);
    }

    private static function safeExternalUrl(?string $url): string
    {
        $url = trim((string) $url);
        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url : '#';
    }

    /**
     * Categoría activa según la URL, tolerando category=ID y category[]=ID.
     *
     * Los filtros del catálogo permiten marcar varias categorías, así que el
     * parámetro puede llegar como arreglo; para resaltar el enlace del menú
     * basta la primera.
     */
    public static function currentCategoryId(): ?string
    {
        $valor = request('category');

        if (is_array($valor)) {
            $valor = reset($valor);
        }

        $valor = is_scalar($valor) ? trim((string) $valor) : '';

        return $valor === '' ? null : $valor;
    }
}
