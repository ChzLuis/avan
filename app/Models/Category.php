<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model {
    protected $fillable = ['project_id', 'name', 'image_url', 'color', 'sort_order', 'is_active', 'type', 'parent_id'];
    protected $casts = ['is_active' => 'boolean'];

    public function project()  { return $this->belongsTo(Project::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function services() { return $this->hasMany(Service::class); }

    public function parent()   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order'); }

    public function scopeRoots($query)    { return $query->whereNull('parent_id'); }
    public function scopeOfType($query, string $type) { return $query->where('type', $type); }
}
