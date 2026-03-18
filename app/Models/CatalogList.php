<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogList extends Model
{
    protected $fillable = ['project_id', 'name', 'type', 'description', 'color', 'is_active', 'is_system', 'sort_order'];
    protected $casts = ['is_active' => 'boolean', 'is_system' => 'boolean'];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function values(): HasMany    { return $this->hasMany(CatalogValue::class); }
}
