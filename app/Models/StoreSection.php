<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSection extends Model
{
    protected $fillable = [
        'project_id', 'page', 'component', 'variant', 'draft_variant', 'content', 'draft_content',
        'sort_order', 'draft_sort_order', 'has_draft', 'is_enabled', 'draft_is_enabled',
        'show_desktop', 'show_tablet', 'show_mobile', 'publish_from', 'publish_until', 'published_at',
    ];
    protected $casts = [
        'content' => 'array', 'draft_content' => 'array', 'is_enabled' => 'boolean',
        'draft_is_enabled' => 'boolean', 'has_draft' => 'boolean', 'show_desktop' => 'boolean',
        'show_tablet' => 'boolean', 'show_mobile' => 'boolean', 'publish_from' => 'datetime',
        'publish_until' => 'datetime', 'published_at' => 'datetime',
    ];
    public function project() { return $this->belongsTo(Project::class); }

    public function contentForPreview(): array
    {
        return $this->has_draft && is_array($this->draft_content) ? $this->draft_content : ($this->content ?? []);
    }

    public function variantForPreview(): ?string
    {
        return $this->has_draft ? ($this->draft_variant ?? $this->variant) : $this->variant;
    }

    public function enabledForPreview(): bool
    {
        return $this->has_draft && $this->draft_is_enabled !== null ? $this->draft_is_enabled : $this->is_enabled;
    }
}
