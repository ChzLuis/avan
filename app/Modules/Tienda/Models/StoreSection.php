<?php

namespace App\Modules\Tienda\Models;

use App\Models\Project;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class StoreSection extends Model
{
    protected $fillable = [
        'project_id', 'page', 'component', 'variant', 'draft_variant', 'content', 'draft_content',
        'sort_order', 'draft_sort_order', 'has_draft', 'is_enabled', 'draft_is_enabled',
        'show_desktop', 'draft_show_desktop', 'show_tablet', 'draft_show_tablet',
        'show_mobile', 'draft_show_mobile', 'publish_from', 'draft_publish_from',
        'publish_until', 'draft_publish_until', 'published_at',
    ];
    protected $casts = [
        'content' => 'array', 'draft_content' => 'array', 'is_enabled' => 'boolean',
        'draft_is_enabled' => 'boolean', 'has_draft' => 'boolean', 'show_desktop' => 'boolean',
        'draft_show_desktop' => 'boolean', 'show_tablet' => 'boolean', 'draft_show_tablet' => 'boolean',
        'show_mobile' => 'boolean', 'draft_show_mobile' => 'boolean', 'publish_from' => 'datetime',
        'draft_publish_from' => 'datetime', 'publish_until' => 'datetime',
        'draft_publish_until' => 'datetime', 'published_at' => 'datetime',
    ];
    public function project() { return $this->belongsTo(Project::class); }

    public function contentForPreview(): array
    {
        $content = $this->has_draft ? $this->draft_content : $this->content;

        return is_array($content) ? $content : [];
    }

    public function variantForPreview(): ?string
    {
        return $this->has_draft ? $this->draft_variant : $this->variant;
    }

    public function enabledForPreview(): bool
    {
        return (bool) ($this->has_draft ? $this->draft_is_enabled : $this->is_enabled);
    }

    public function sortOrderForPreview(): int
    {
        return (int) ($this->has_draft ? $this->draft_sort_order : $this->sort_order);
    }

    public function showDesktopForPreview(): bool
    {
        return (bool) ($this->has_draft ? $this->draft_show_desktop : $this->show_desktop);
    }

    public function showTabletForPreview(): bool
    {
        return (bool) ($this->has_draft ? $this->draft_show_tablet : $this->show_tablet);
    }

    public function showMobileForPreview(): bool
    {
        return (bool) ($this->has_draft ? $this->draft_show_mobile : $this->show_mobile);
    }

    public function publishFromForPreview(): ?CarbonInterface
    {
        return $this->has_draft ? $this->draft_publish_from : $this->publish_from;
    }

    public function publishUntilForPreview(): ?CarbonInterface
    {
        return $this->has_draft ? $this->draft_publish_until : $this->publish_until;
    }

    public function derivedStates(bool $preview = false): array
    {
        $content = $preview ? $this->contentForPreview() : ($this->content ?? []);
        $enabled = $preview ? $this->enabledForPreview() : (bool) $this->is_enabled;
        $desktop = $preview ? $this->showDesktopForPreview() : (bool) $this->show_desktop;
        $tablet = $preview ? $this->showTabletForPreview() : (bool) $this->show_tablet;
        $mobile = $preview ? $this->showMobileForPreview() : (bool) $this->show_mobile;
        $from = $preview ? $this->publishFromForPreview() : $this->publish_from;
        $until = $preview ? $this->publishUntilForPreview() : $this->publish_until;
        $now = now();

        return array_values(array_filter([
            $preview && $this->has_draft ? 'draft' : 'published',
            !$enabled || (!$desktop && !$tablet && !$mobile) ? 'hidden' : null,
            empty($content) ? 'empty' : null,
            $from !== null && $from->isFuture() ? 'scheduled' : null,
            $until !== null && $until->lt($now) ? 'expired' : null,
        ]));
    }
}
