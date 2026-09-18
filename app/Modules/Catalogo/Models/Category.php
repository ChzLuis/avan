<?php
namespace App\Modules\Catalogo\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
class Category extends Model {
    protected $fillable = ['project_id', 'name', 'slug', 'image_url', 'color', 'sort_order', 'is_active', 'type', 'parent_id'];
    protected $casts = ['is_active' => 'boolean'];

    /**
     * Slug para URLs legibles (/computadoras en vez de ?category=394).
     *
     * Se genera al crear y NO se regenera al renombrar: si el cliente cambia
     * "Laptops" por "Laptops y notebooks", el enlace que ya compartió por
     * WhatsApp tiene que seguir funcionando. Solo se rellena si está vacío.
     *
     * La unicidad es por proyecto (índice `uq_cat_slug`), así que dos tiendas
     * distintas pueden tener ambas su categoría "accesorios".
     */


    public function project()  { return $this->belongsTo(Project::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function services() { return $this->hasMany(Service::class); }

    public function parent()   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order'); }

    public function scopeRoots($query)    { return $query->whereNull('parent_id'); }
    public function scopeOfType($query, string $type) { return $query->where('type', $type); }

    protected static function booted(): void
    {
        // Slug para URLs legibles. Se rellena solo si está vacío: al renombrar
        // una categoría NO se regenera, para que el enlace que el cliente ya
        // compartió por WhatsApp siga funcionando.
        static::saving(function (self $cat) {
            if (filled($cat->slug)) {
                return;
            }

            $base = \Illuminate\Support\Str::slug((string) $cat->name) ?: 'categoria';
            $slug = $base;
            $i = 2;

            while (static::where('project_id', $cat->project_id)
                ->where('slug', $slug)
                ->when($cat->exists, fn ($q) => $q->where('id', '!=', $cat->id))
                ->exists()
            ) {
                $slug = $base.'-'.$i++;
            }

            $cat->slug = $slug;
        });

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
