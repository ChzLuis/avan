<?php

namespace App\Modules\Tienda\Storefront;

use App\Models\Category;
use App\Models\Project;
use App\Modules\Tienda\Models\StoreMenu;
use App\Modules\Tienda\Models\StoreMenuItem;
use App\Modules\Tienda\Models\StorePage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fase 1D — Escritura canónica y aislada de menús (store_menus / store_menu_items).
 *
 * Garantías:
 *  - Aislamiento por proyecto (todo scope por project_id + store_menu_id).
 *  - Transacciones en operaciones compuestas (reorder).
 *  - Destinos internos verificados contra la misma tienda; URLs externas http/https.
 *  - Orden estable (sort_order incremental), un solo nivel de submenú, sin ciclos.
 *  - Booleanos explícitos; no escribe aliases históricos.
 */
final class StoreMenuWriteService
{
    /**
     * Crea un ítem al final del menú del proyecto.
     *
     * @param array $data Datos ya validados por el request (label, destination_type, ...).
     */
    public function createItem(Project $project, StoreMenu $menu, array $data): StoreMenuItem
    {
        return DB::transaction(function () use ($project, $menu, $data) {
            $data = $this->normalize($project, $menu, $data, null);
            $data['project_id'] = $project->id;
            $data['store_menu_id'] = $menu->id;
            $data['sort_order'] = ($menu->items()->max('sort_order') ?? 0) + 10;
            return $menu->items()->create($data);
        });
    }

    /**
     * Actualiza un ítem existente del menú del proyecto (solo campos presentes).
     */
    public function updateItem(Project $project, StoreMenu $menu, int $itemId, array $data): StoreMenuItem
    {
        return DB::transaction(function () use ($project, $menu, $itemId, $data) {
            $item = $menu->items()->where('project_id', $project->id)->findOrFail($itemId);
            $data = $this->normalize($project, $menu, $data, $item->id);
            $item->update($data);
            return $item->refresh();
        });
    }

    /**
     * Elimina un ítem del menú, sin dejar el orden corrupto (los hijos suben de nivel).
     */
    public function deleteItem(Project $project, StoreMenu $menu, int $itemId): void
    {
        DB::transaction(function () use ($project, $menu, $itemId) {
            $item = $menu->items()->where('project_id', $project->id)->findOrFail($itemId);
            // Los hijos del ítem eliminado pasan a nivel raíz para no quedar huérfanos.
            $menu->items()->where('project_id', $project->id)->where('parent_id', $item->id)
                ->update(['parent_id' => null]);
            $item->delete();
        });
    }

    /**
     * Reordena los ítems del menú de forma transaccional. Rechaza IDs ajenos,
     * ciclos y más de un nivel de submenú.
     *
     * @param array<int,array{id:int,parent_id:?int,sort_order:int}> $rows
     */
    public function reorder(Project $project, StoreMenu $menu, array $rows): int
    {
        $items = $menu->items()->where('project_id', $project->id)->get()->keyBy('id');
        $submittedIds = collect($rows)->pluck('id');
        if ($submittedIds->diff($items->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'El menú contiene opciones de otra tienda.']);
        }

        return DB::transaction(function () use ($rows, $items) {
            $count = 0;
            foreach ($rows as $row) {
                $item = $items->get((int) $row['id']);
                $parentId = filled($row['parent_id'] ?? null) ? (int) $row['parent_id'] : null;
                if ($parentId === $item->id || ($parentId && !$items->has($parentId))) {
                    throw ValidationException::withMessages(['items' => 'La jerarquía del menú no es válida.']);
                }
                if ($parentId && $items->get($parentId)?->parent_id) {
                    throw ValidationException::withMessages(['items' => 'El menú admite un nivel de submenú.']);
                }
                $item->update(['parent_id' => $parentId, 'sort_order' => (int) $row['sort_order']]);
                $count++;
            }
            return $count;
        });
    }

    /**
     * Verifica propiedad del destino y normaliza destination_id/url/booleanos.
     * Preserva el contrato: destino interno debe pertenecer a la misma tienda,
     * URL externa debe ser http/https.
     */
    private function normalize(Project $project, StoreMenu $menu, array $data, ?int $itemId): array
    {
        // parent válido y de la misma tienda
        if (!empty($data['parent_id'])) {
            $parent = StoreMenuItem::where('project_id', $project->id)
                ->where('store_menu_id', $menu->id)->find($data['parent_id']);
            if (!$parent || $parent->id === $itemId || $parent->parent_id) {
                throw ValidationException::withMessages(['parent_id' => 'Selecciona una opción principal válida.']);
            }
        }

        $type = $data['destination_type'];
        if (in_array($type, ['category', 'subcategory'], true)) {
            $category = Category::where('project_id', $project->id)->find($data['destination_id'] ?? 0);
            if (!$category
                || ($type === 'category' && $category->parent_id)
                || ($type === 'subcategory' && !$category->parent_id)) {
                throw ValidationException::withMessages(['destination_id' => 'Selecciona una categoría válida de esta tienda.']);
            }
        } elseif ($type === 'page') {
            if (!StorePage::where('project_id', $project->id)->whereKey($data['destination_id'] ?? 0)->exists()) {
                throw ValidationException::withMessages(['destination_id' => 'Selecciona una página válida de esta tienda.']);
            }
        } elseif ($type === 'external') {
            $scheme = strtolower((string) parse_url((string) ($data['url'] ?? ''), PHP_URL_SCHEME));
            if (!filter_var($data['url'] ?? null, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                throw ValidationException::withMessages(['url' => 'La URL externa debe comenzar con http:// o https://.']);
            }
        }

        $data['destination_id'] = in_array($type, ['category', 'subcategory', 'page'], true)
            ? ($data['destination_id'] ?? null) : null;
        $data['url'] = $type === 'external' ? $data['url'] : null;

        return $data;
    }
}
