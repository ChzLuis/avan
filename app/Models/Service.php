<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Service extends Model {
    protected $fillable = ['project_id', 'category_id', 'name', 'description', 'notes', 'price', 'duration_min', 'modality', 'image_url', 'is_available', 'sort_order'];
    protected $casts = ['is_available' => 'boolean', 'price' => 'decimal:2'];
    public function project()      { return $this->belongsTo(Project::class); }
    public function category()     { return $this->belongsTo(Category::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
}
