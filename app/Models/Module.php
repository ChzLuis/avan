<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Module extends Model {
    protected $fillable = ['name', 'key', 'description', 'icon', 'route', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function projects() { return $this->belongsToMany(Project::class, 'project_modules')->withPivot('is_active'); }
}
