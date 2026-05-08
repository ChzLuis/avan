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

    protected static function booted(): void
    {
        // Impedir que se asigne un parent_id de otro proyecto
        static::saving(function (self $category) {
            if ($category->parent_id) {
                $parentProjectId = static::where('id', $category->parent_id)->value('project_id');
                if ($parentProjectId && $parentProjectId !== $category->project_id) {
                    throw new \RuntimeException(
                        "parent_id {$category->parent_id} pertenece al proyecto {$parentProjectId}, no al proyecto {$category->project_id}."
                    );
                }
            }
        });
    }
}
