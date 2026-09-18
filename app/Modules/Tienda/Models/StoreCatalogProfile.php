<?php

namespace App\Modules\Tienda\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class StoreCatalogProfile extends Model
{
    protected $fillable = [
        'project_id', 'name', 'slug', 'menu_label', 'description',
        'is_enabled', 'is_default', 'show_in_menu', 'sort_order',
        'logo_path', 'mobile_logo_path', 'favicon_path',
        'primary_color', 'secondary_color', 'header_bg_color', 'header_text_color',
        'button_color', 'footer_bg_color', 'announcement_bg_color',
        'hero_desktop_path', 'hero_mobile_path', 'hero_title', 'hero_description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Columnas de identidad visual que un perfil puede sobrescribir. */
    public const VISUAL_KEYS = [
        'logo_path', 'mobile_logo_path', 'favicon_path',
        'primary_color', 'secondary_color', 'header_bg_color', 'header_text_color',
        'button_color', 'footer_bg_color', 'announcement_bg_color',
        'hero_desktop_path', 'hero_mobile_path', 'hero_title', 'hero_description',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'store_catalog_profile_category');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'store_catalog_profile_product');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('is_enabled', true)->where('show_in_menu', true);
    }

    /** ¿El perfil define alguna sobreescritura visual? Si no, sólo filtra el catálogo. */
    public function hasVisualIdentity(): bool
    {
        foreach (self::VISUAL_KEYS as $key) {
            if (! empty($this->{$key})) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mapa de settings que este perfil SOBREESCRIBE, para fusionar sobre los
     * settings globales de la tienda. Sólo incluye claves con valor: lo vacío
     * hace fallback a la identidad global (no se emite → no parpadea).
     *
     * Las claves de settings coinciden con las que consumen las plantillas
     * (logo_url, primary_color, header_bg_color, hero_title…).
     */
    public function settingOverrides(): array
    {
        $map = [
            'logo_path' => ['logo_url', 'logo'],
            'mobile_logo_path' => ['mobile_logo_url', 'logo_mobile'],
            'favicon_path' => ['favicon_url', 'favicon'],
            'primary_color' => ['primary_color'],
            'secondary_color' => ['secondary_color'],
            'header_bg_color' => ['header_bg_color'],
            'header_text_color' => ['header_text_color'],
            'button_color' => ['button_color'],
            'footer_bg_color' => ['footer_bg_color'],
            // La barra de avisos: sin esto, al cambiar de mundo cambiaba
            // todo menos ella, porque su color vive en un ajuste global.
            'announcement_bg_color' => ['announcement_bg'],
            'hero_desktop_path' => ['hero_image', 'hero_image_desktop'],
            'hero_mobile_path' => ['hero_image_mobile'],
            'hero_title' => ['hero_title'],
            'hero_description' => ['hero_subtitle'],
        ];

        $overrides = [];
        foreach ($map as $column => $settingKeys) {
            $value = $this->{$column};
            if ($value === null || $value === '') {
                continue; // fallback global
            }
            foreach ($settingKeys as $key) {
                $overrides[$key] = $value;
            }
        }

        return $overrides;
    }

    protected static function booted(): void
    {
        static::saving(function (self $profile) {
            if (blank($profile->slug) && filled($profile->name)) {
                $profile->slug = Str::slug($profile->name);
            }
        });
    }
}
