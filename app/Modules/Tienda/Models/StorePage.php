<?php

namespace App\Modules\Tienda\Models;

use App\Models\Project;

use Illuminate\Database\Eloquent\Model;

class StorePage extends Model
{
    protected $fillable = [
        'project_id', 'key', 'title', 'content', 'is_enabled',
        // Ciclo borrador -> publicacion, igual que `store_sections`.
        'draft_title', 'draft_content', 'draft_is_enabled', 'has_draft', 'published_at',
    ];

    protected $casts = [
        'content' => 'array', 'is_enabled' => 'boolean',
        'draft_content' => 'array', 'draft_is_enabled' => 'boolean',
        'has_draft' => 'boolean', 'published_at' => 'datetime',
    ];

    public function project() { return $this->belongsTo(Project::class); }

    /** Lo que se ve en la VISTA PREVIA: el borrador si existe, si no lo publicado. */
    public function tituloEfectivo(): string
    {
        return (string) ($this->has_draft ? ($this->draft_title ?? $this->title) : $this->title);
    }

    /** @return array<string,mixed> */
    public function contenidoEfectivo(): array
    {
        return (array) ($this->has_draft ? ($this->draft_content ?? $this->content) : $this->content) ?: [];
    }

    public function visibleEfectivo(): bool
    {
        return (bool) ($this->has_draft ? ($this->draft_is_enabled ?? $this->is_enabled) : $this->is_enabled);
    }

    /** Promueve el borrador a publicado. Devuelve si habia algo que publicar. */
    public function publicarBorrador(): bool
    {
        if (! $this->has_draft) {
            return false;
        }

        $this->forceFill([
            'title' => $this->draft_title ?? $this->title,
            'content' => $this->draft_content ?? $this->content,
            'is_enabled' => $this->draft_is_enabled ?? $this->is_enabled,
            'draft_title' => null, 'draft_content' => null, 'draft_is_enabled' => null,
            'has_draft' => false, 'published_at' => now(),
        ])->save();

        return true;
    }

    /** Descarta el borrador y vuelve al ultimo publicado. */
    public function descartarBorrador(): void
    {
        $this->forceFill([
            'draft_title' => null, 'draft_content' => null,
            'draft_is_enabled' => null, 'has_draft' => false,
        ])->save();
    }
}
