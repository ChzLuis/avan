<?php

namespace App\Models;

use App\Models\Traits\HasProjectScope;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasProjectScope;

    protected $fillable = [
        'project_id', 'name', 'slug', 'type', 'is_variant',
        'is_filterable', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_variant' => 'boolean',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function values() { return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order')->orderBy('label'); }
}
