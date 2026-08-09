<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignTemplate extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'description', 'category', 'thumbnail_path',
        'source_project_id', 'is_favorite', 'is_default', 'archived_at',
    ];

    protected $casts = [
        'is_favorite' => 'boolean', 'is_default' => 'boolean', 'archived_at' => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(DesignTemplateVersion::class)->orderByDesc('version');
    }

    public function latestVersion(): ?DesignTemplateVersion
    {
        return $this->versions()->first();
    }

    public function scopeActiveFor($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId)->whereNull('archived_at')
            ->orderByDesc('is_favorite')->orderByDesc('updated_at');
    }
}
