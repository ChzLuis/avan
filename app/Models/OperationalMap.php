<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalMap extends Model
{
    protected $fillable = [
        'project_id', 'name', 'slug', 'background_url',
        'canvas_width', 'canvas_height', 'grid_enabled', 'grid_size',
        'snap_enabled', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'grid_enabled' => 'boolean',
        'snap_enabled' => 'boolean',
        'is_default'   => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function objects(): HasMany
    {
        return $this->hasMany(OperationalObject::class, 'map_id')->orderBy('sort_order');
    }

    public function activeObjects(): HasMany
    {
        return $this->objects()->where('is_active', true);
    }
}
