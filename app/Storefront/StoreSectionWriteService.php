<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\StoreSection;
use App\Support\StorefrontSections;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StoreSectionWriteService
{
    private const PAGE = 'home';

    private const DRAFT_MAP = [
        'content' => 'draft_content',
        'variant' => 'draft_variant',
        'is_enabled' => 'draft_is_enabled',
        'sort_order' => 'draft_sort_order',
        'show_desktop' => 'draft_show_desktop',
        'show_tablet' => 'draft_show_tablet',
        'show_mobile' => 'draft_show_mobile',
        'publish_from' => 'draft_publish_from',
        'publish_until' => 'draft_publish_until',
    ];

    public function ensureHomeSections(Project $project): Collection
    {
        $this->validateProject($project);
        $settings = $project->relationLoaded('settings')
            ? $project->settings->pluck('value', 'key')->all()
            : $project->settings()->pluck('value', 'key')->all();
        $defaults = StorefrontSections::defaults($project, $settings);

        return DB::transaction(function () use ($project, $defaults) {
            $existing = StoreSection::query()
                ->where('project_id', $project->id)
                ->where('page', self::PAGE)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('component');

            foreach (array_keys(StorefrontSections::COMPONENTS) as $index => $component) {
                if ($existing->has($component)) {
                    continue;
                }

                $definition = $defaults[$component];
                $section = StoreSection::query()->firstOrCreate(
                    ['project_id' => $project->id, 'page' => self::PAGE, 'component' => $component],
                    [
                        'variant' => $definition['variant'],
                        'content' => $definition['content'],
                        'sort_order' => ($index + 1) * 10,
                        'is_enabled' => $definition['enabled'],
                        'show_desktop' => true,
                        'show_tablet' => true,
                        'show_mobile' => true,
                        'published_at' => now(),
                    ],
                );
                $existing->put($component, $section);
            }

            return $existing
                ->only(array_keys(StorefrontSections::COMPONENTS))
                ->sortBy(fn (StoreSection $section) => [$section->sort_order, $section->id])
                ->values();
        });
    }

    public function saveDraft(Project $project, string $page, string $component, array $changes): StoreSection
    {
        $this->validateScope($project, $page, $component);

        return DB::transaction(function () use ($project, $page, $component, $changes) {
            $section = $this->lockedSection($project, $page, $component);
            $this->applyDraftChanges($section, $changes);
            $section->save();

            return $section->fresh();
        });
    }

    public function saveAndPublish(Project $project, string $page, string $component, array $changes): StoreSection
    {
        $this->validateScope($project, $page, $component);

        return DB::transaction(function () use ($project, $page, $component, $changes) {
            $section = $this->lockedSection($project, $page, $component);
            $this->applyDraftChanges($section, $changes);
            $this->publishSnapshot($section);
            $section->save();

            return $section->fresh();
        });
    }

    public function publishOne(Project $project, string $page, string $component): bool
    {
        $this->validateScope($project, $page, $component);

        return DB::transaction(function () use ($project, $page, $component) {
            $section = $this->lockedSection($project, $page, $component, false);
            if ($section === null || !$section->has_draft) {
                return false;
            }

            $this->validateDraftDates($section);
            $this->publishSnapshot($section);
            $section->save();

            return true;
        });
    }

    public function publishAll(Project $project, string $page): int
    {
        $this->validatePageAndProject($project, $page);

        return DB::transaction(function () use ($project, $page) {
            $sections = StoreSection::query()
                ->where('project_id', $project->id)
                ->where('page', $page)
                ->where('has_draft', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($sections as $section) {
                $this->validateComponent($section->component);
                $this->validateDraftDates($section);
            }

            foreach ($sections as $section) {
                $this->publishSnapshot($section);
                $section->save();
            }

            return $sections->count();
        });
    }

    public function discardDraft(Project $project, string $page, string $component): bool
    {
        $this->validateScope($project, $page, $component);

        return DB::transaction(function () use ($project, $page, $component) {
            $section = $this->lockedSection($project, $page, $component, false);
            if ($section === null || !$section->has_draft) {
                return false;
            }

            $this->clearDraft($section);
            $section->save();

            return true;
        });
    }

    public function reorderDraft(Project $project, string $page, array $sectionIds): int
    {
        $this->validatePageAndProject($project, $page);
        $ids = array_map(static fn ($id) => (int) $id, array_values($sectionIds));

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['order' => 'El orden contiene secciones repetidas.']);
        }

        if (count($ids) !== count(StorefrontSections::COMPONENTS)) {
            throw ValidationException::withMessages(['order' => 'El orden debe incluir todas las secciones canónicas de Inicio.']);
        }

        return DB::transaction(function () use ($project, $page, $ids) {
            $sections = StoreSection::query()
                ->where('project_id', $project->id)
                ->where('page', $page)
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($sections->count() !== count($ids)) {
                throw ValidationException::withMessages(['order' => 'Todas las secciones deben pertenecer al proyecto y página activos.']);
            }

            $components = $sections->pluck('component')->sort()->values()->all();
            $required = collect(array_keys(StorefrontSections::COMPONENTS))->sort()->values()->all();
            if ($components !== $required) {
                throw ValidationException::withMessages(['order' => 'El orden contiene componentes faltantes o no canónicos.']);
            }

            foreach ($ids as $index => $id) {
                $section = $sections->get($id);
                $this->startDraftSnapshot($section);
                $section->draft_sort_order = ($index + 1) * 10;
                $section->save();
            }

            return count($ids);
        });
    }

    private function applyDraftChanges(StoreSection $section, array $changes): void
    {
        $this->startDraftSnapshot($section);

        foreach (self::DRAFT_MAP as $input => $draftColumn) {
            if (array_key_exists($input, $changes)) {
                $section->{$draftColumn} = $changes[$input];
            }
        }

        $section->has_draft = true;
        $this->validateDraftDates($section);
    }

    private function startDraftSnapshot(StoreSection $section): void
    {
        if ($section->has_draft) {
            return;
        }

        foreach (self::DRAFT_MAP as $published => $draft) {
            $section->{$draft} = $section->{$published};
        }
        $section->has_draft = true;
    }

    private function publishSnapshot(StoreSection $section): void
    {
        foreach (self::DRAFT_MAP as $published => $draft) {
            $section->{$published} = $section->{$draft};
        }
        $section->published_at = now();
        $this->clearDraft($section);
    }

    private function clearDraft(StoreSection $section): void
    {
        foreach (self::DRAFT_MAP as $draft) {
            $section->{$draft} = null;
        }
        $section->has_draft = false;
    }

    private function validateDraftDates(StoreSection $section): void
    {
        $from = $section->draft_publish_from;
        $until = $section->draft_publish_until;

        if ($from !== null && $until !== null && $until->lt($from)) {
            throw ValidationException::withMessages([
                'publish_until' => 'La fecha final debe ser posterior o igual a la fecha inicial.',
            ]);
        }
    }

    private function lockedSection(Project $project, string $page, string $component, bool $create = true): ?StoreSection
    {
        $query = StoreSection::query()
            ->where('project_id', $project->id)
            ->where('page', $page)
            ->where('component', $component);

        $section = (clone $query)->lockForUpdate()->first();
        if ($section !== null || !$create) {
            return $section;
        }

        $definition = StorefrontSections::definition($project, $component);
        $position = array_search($component, array_keys(StorefrontSections::COMPONENTS), true);
        StoreSection::query()->firstOrCreate(
            ['project_id' => $project->id, 'page' => $page, 'component' => $component],
            [
                'variant' => $definition['variant'],
                'content' => $definition['content'],
                'sort_order' => (($position === false ? 0 : $position) + 1) * 10,
                'is_enabled' => $definition['enabled'],
                'show_desktop' => true,
                'show_tablet' => true,
                'show_mobile' => true,
                'published_at' => now(),
            ],
        );

        return (clone $query)->lockForUpdate()->firstOrFail();
    }

    private function validateScope(Project $project, string $page, string $component): void
    {
        $this->validatePageAndProject($project, $page);
        $this->validateComponent($component);
    }

    private function validatePageAndProject(Project $project, string $page): void
    {
        $this->validateProject($project);
        if ($page !== self::PAGE) {
            throw ValidationException::withMessages(['page' => 'Solo se admite la página canónica home.']);
        }
    }

    private function validateProject(Project $project): void
    {
        if (!$project->exists || $project->getKey() === null) {
            throw ValidationException::withMessages(['project' => 'El proyecto indicado no existe.']);
        }
    }

    private function validateComponent(string $component): void
    {
        if (!array_key_exists($component, StorefrontSections::COMPONENTS)) {
            throw ValidationException::withMessages(['component' => 'El componente indicado no es canónico.']);
        }
    }
}
