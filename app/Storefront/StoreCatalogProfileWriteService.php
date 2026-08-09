<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\StoreCatalogProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio canónico de escritura para perfiles de catálogo.
 * Toda mutación pasa por aquí: creación, actualización, orden, perfil por
 * defecto, visibilidad, asignaciones y borrado seguro. Aislamiento estricto
 * por proyecto y operaciones transaccionales.
 */
final class StoreCatalogProfileWriteService
{
    private const VISUAL_TEXT_KEYS = [
        'primary_color', 'secondary_color', 'header_bg_color', 'header_text_color',
        'button_color', 'footer_bg_color', 'hero_title', 'hero_description',
    ];

    /** Crea un perfil con slug único por proyecto. */
    public function create(Project $project, array $data): StoreCatalogProfile
    {
        return DB::transaction(function () use ($project, $data) {
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages(['name' => 'El nombre del perfil es obligatorio.']);
            }

            $profile = new StoreCatalogProfile();
            $profile->project_id = $project->id;
            $profile->name = $name;
            $profile->slug = $this->uniqueSlug($project, $data['slug'] ?? $name);
            $profile->menu_label = $this->nullableString($data['menu_label'] ?? null) ?? $name;
            $profile->description = $this->nullableString($data['description'] ?? null);
            $profile->is_enabled = (bool) ($data['is_enabled'] ?? true);
            $profile->show_in_menu = (bool) ($data['show_in_menu'] ?? true);
            $profile->sort_order = (int) ($data['sort_order'] ?? $this->nextSortOrder($project));
            $this->fillVisual($profile, $data);
            $profile->save();

            if (! empty($data['is_default'])) {
                $this->setDefault($project, $profile);
            }
            if (array_key_exists('category_ids', $data)) {
                $this->syncCategories($project, $profile, (array) $data['category_ids']);
            }
            if (array_key_exists('product_ids', $data)) {
                $this->syncProducts($project, $profile, (array) $data['product_ids']);
            }

            return $profile->fresh();
        });
    }

    /** Actualiza un perfil existente (validando que pertenezca al proyecto). */
    public function update(Project $project, StoreCatalogProfile $profile, array $data): StoreCatalogProfile
    {
        $this->assertOwnership($project, $profile);

        return DB::transaction(function () use ($project, $profile, $data) {
            if (array_key_exists('name', $data)) {
                $name = trim((string) $data['name']);
                if ($name === '') {
                    throw ValidationException::withMessages(['name' => 'El nombre del perfil es obligatorio.']);
                }
                $profile->name = $name;
            }
            if (array_key_exists('slug', $data)) {
                $profile->slug = $this->uniqueSlug($project, $data['slug'] ?: $profile->name, $profile->id);
            }
            foreach (['menu_label', 'description'] as $key) {
                if (array_key_exists($key, $data)) {
                    $profile->{$key} = $this->nullableString($data[$key]);
                }
            }
            foreach (['is_enabled', 'show_in_menu'] as $key) {
                if (array_key_exists($key, $data)) {
                    $profile->{$key} = (bool) $data[$key];
                }
            }
            if (array_key_exists('sort_order', $data)) {
                $profile->sort_order = (int) $data['sort_order'];
            }
            $this->fillVisual($profile, $data);
            $profile->save();

            if (array_key_exists('is_default', $data)) {
                if ($data['is_default']) {
                    $this->setDefault($project, $profile);
                } elseif ($profile->is_default) {
                    $profile->update(['is_default' => false]);
                }
            }
            if (array_key_exists('category_ids', $data)) {
                $this->syncCategories($project, $profile, (array) $data['category_ids']);
            }
            if (array_key_exists('product_ids', $data)) {
                $this->syncProducts($project, $profile, (array) $data['product_ids']);
            }

            return $profile->fresh();
        });
    }

    public function setEnabled(Project $project, StoreCatalogProfile $profile, bool $enabled): StoreCatalogProfile
    {
        $this->assertOwnership($project, $profile);
        $profile->update(['is_enabled' => $enabled]);

        return $profile;
    }

    /** Marca este perfil como predeterminado y quita la marca a los demás del proyecto. */
    public function setDefault(Project $project, StoreCatalogProfile $profile): StoreCatalogProfile
    {
        $this->assertOwnership($project, $profile);

        return DB::transaction(function () use ($project, $profile) {
            $project->catalogProfiles()->where('id', '!=', $profile->id)->update(['is_default' => false]);
            $profile->update(['is_default' => true]);

            return $profile->fresh();
        });
    }

    /** Reordena los perfiles según una lista de IDs (sólo los del proyecto). */
    public function reorder(Project $project, array $orderedIds): void
    {
        DB::transaction(function () use ($project, $orderedIds) {
            $owned = $project->catalogProfiles()->pluck('id')->all();
            $position = 0;
            foreach ($orderedIds as $id) {
                if (in_array((int) $id, $owned, true)) {
                    $project->catalogProfiles()->whereKey($id)->update(['sort_order' => $position++]);
                }
            }
        });
    }

    public function delete(Project $project, StoreCatalogProfile $profile): void
    {
        $this->assertOwnership($project, $profile);
        // Los pivotes se limpian por cascadeOnDelete de las FK.
        $profile->delete();
    }

    /** Asigna categorías, aceptando sólo IDs que pertenezcan al proyecto. */
    public function syncCategories(Project $project, StoreCatalogProfile $profile, array $categoryIds): void
    {
        $this->assertOwnership($project, $profile);
        $valid = $project->categories()
            ->whereIn('id', Arr::map($categoryIds, fn ($v) => (int) $v))
            ->pluck('id')->all();
        $profile->categories()->sync($valid);
    }

    /** Asigna productos, aceptando sólo IDs que pertenezcan al proyecto. */
    public function syncProducts(Project $project, StoreCatalogProfile $profile, array $productIds): void
    {
        $this->assertOwnership($project, $profile);
        $valid = $project->products()
            ->whereIn('id', Arr::map($productIds, fn ($v) => (int) $v))
            ->pluck('id')->all();
        $profile->products()->sync($valid);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function fillVisual(StoreCatalogProfile $profile, array $data): void
    {
        // Rutas de imagen (logo, hero…): se escriben tal cual si vienen en $data.
        foreach (['logo_path', 'mobile_logo_path', 'favicon_path', 'hero_desktop_path', 'hero_mobile_path'] as $key) {
            if (array_key_exists($key, $data)) {
                $profile->{$key} = $this->nullableString($data[$key]);
            }
        }
        foreach (self::VISUAL_TEXT_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $profile->{$key} = $this->nullableString($data[$key]);
            }
        }
    }

    private function uniqueSlug(Project $project, string $raw, ?int $ignoreId = null): string
    {
        $base = Str::slug($raw) ?: 'perfil';
        $slug = $base;
        $i = 2;
        while ($project->catalogProfiles()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function nextSortOrder(Project $project): int
    {
        return (int) $project->catalogProfiles()->max('sort_order') + 1;
    }

    private function nullableString($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === '' || $value === null) ? null : (string) $value;
    }

    private function assertOwnership(Project $project, StoreCatalogProfile $profile): void
    {
        if ((int) $profile->project_id !== (int) $project->id) {
            throw ValidationException::withMessages([
                'profile' => 'El perfil no pertenece a esta tienda.',
            ]);
        }
    }
}
