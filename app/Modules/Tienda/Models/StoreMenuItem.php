<?php

namespace App\Modules\Tienda\Models;

use App\Models\Project;

use Illuminate\Database\Eloquent\Model;

class StoreMenuItem extends Model
{
    public const DESTINATIONS = [
        'home', 'shop', 'products', 'category', 'subcategory',
        'brands', 'promotions', 'catalog_pdf',
        'about', 'contact', 'blog', 'page', 'external',
    ];

    protected $fillable = [
        'project_id', 'store_menu_id', 'parent_id', 'label', 'destination_type',
        'destination_id', 'url', 'target', 'sort_order', 'is_enabled',
        'show_desktop', 'show_tablet', 'show_mobile',
    ];
    protected $casts = [
        'is_enabled' => 'boolean', 'show_desktop' => 'boolean',
        'show_tablet' => 'boolean', 'show_mobile' => 'boolean',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function menu() { return $this->belongsTo(StoreMenu::class, 'store_menu_id'); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id'); }
}
