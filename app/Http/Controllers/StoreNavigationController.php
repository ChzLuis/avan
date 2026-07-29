<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StoreMenuItem;
use App\Models\StorePage;
use App\Support\StorefrontNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreNavigationController extends Controller
{
    private function project()
    {
        $project = app('active_project');
        abort_unless($project, 404);
        $user = auth()->user();
        abort_unless(
            $user && ($user->is_superadmin || $project->owner_id === $user->id || $project->members()->where('user_id', $user->id)->exists()),
            403
        );
        return $project;
    }

    public function updateHeader(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'header_bg_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_hover_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_active_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_font' => ['required', Rule::in(['Inter', 'Poppins', 'Montserrat', 'Lato', 'Nunito', 'Jost', 'Raleway'])],
            'header_font_size' => ['required', 'integer', 'min:12', 'max:20'],
            'header_height' => ['required', 'integer', 'min:56', 'max:120'],
            'header_logo_height' => ['required', 'integer', 'min:28', 'max:80'],
            'header_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'header_mobile_style' => ['required', Rule::in(['drawer', 'compact'])],
            'header_tablet_style' => ['required', Rule::in(['drawer', 'desktop'])],
        ]);

        if ($request->hasFile('header_logo')) {
            $data['header_logo_url'] = $request->file('header_logo')->store("store-header/{$project->id}", 'public');
        }
        unset($data['header_logo']);
        foreach (['header_sticky', 'header_show_search', 'header_show_contact', 'header_show_cart'] as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }
        foreach ($data as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return $this->back('Encabezado guardado. Revisa la vista previa antes de activar la nueva estructura.');
    }

    public function publishStructure(Request $request)
    {
        $project = $this->project();
        $enabled = $request->boolean('enabled');
        if ($enabled && !$request->boolean('confirm')) {
            throw ValidationException::withMessages(['confirm' => 'Confirma que revisaste la vista previa antes de publicar.']);
        }
        StorefrontNavigation::ensure($project);
        $project->settings()->updateOrCreate(['key' => 'storefront_structure_v2'], ['value' => $enabled ? '1' : '0']);
        return $this->back($enabled ? 'Nueva estructura publicada para esta tienda.' : 'La tienda volvió a la estructura anterior. Tus configuraciones se conservaron.');
    }

    public function storeItem(Request $request)
    {
        $project = $this->project();
        $menu = StorefrontNavigation::ensure($project);
        $data = $this->validateItem($request, $project->id, $menu->id);
        $data['project_id'] = $project->id;
        $data['store_menu_id'] = $menu->id;
        $data['sort_order'] = ($menu->items()->max('sort_order') ?? 0) + 10;
        $menu->items()->create($data);
        return $this->back('Opción agregada al menú.');
    }

    public function updateItem(Request $request, int $item)
    {
        $project = $this->project();
        $menu = StorefrontNavigation::ensure($project);
        $menuItem = $menu->items()->where('project_id', $project->id)->findOrFail($item);
        $data = $this->validateItem($request, $project->id, $menu->id, $menuItem->id);
        $menuItem->update($data);
        return $this->back('Opción del menú actualizada.');
    }

    public function destroyItem(int $item)
    {
        $project = $this->project();
        $menu = StorefrontNavigation::ensure($project);
        $menu->items()->where('project_id', $project->id)->findOrFail($item)->delete();
        return $this->back('Opción eliminada del menú. La página o categoría vinculada se conservó.');
    }

    public function reorder(Request $request)
    {
        $project = $this->project();
        $menu = StorefrontNavigation::ensure($project);
        $data = $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.id' => ['required', 'integer'],
            'items.*.parent_id' => ['nullable', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $items = $menu->items()->where('project_id', $project->id)->get()->keyBy('id');
        $submittedIds = collect($data['items'])->pluck('id');
        if ($submittedIds->diff($items->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'El menú contiene opciones de otra tienda.']);
        }

        DB::transaction(function () use ($data, $items) {
            foreach ($data['items'] as $row) {
                $item = $items->get((int) $row['id']);
                $parentId = filled($row['parent_id'] ?? null) ? (int) $row['parent_id'] : null;
                if ($parentId === $item->id || ($parentId && !$items->has($parentId))) {
                    throw ValidationException::withMessages(['items' => 'La jerarquía del menú no es válida.']);
                }
                if ($parentId && $items->get($parentId)?->parent_id) {
                    throw ValidationException::withMessages(['items' => 'El menú admite un nivel de submenú.']);
                }
                $item->update(['parent_id' => $parentId, 'sort_order' => (int) $row['sort_order']]);
            }
        });

        return response()->json(['ok' => true, 'message' => 'Orden del menú guardado.']);
    }

    private function validateItem(Request $request, int $projectId, int $menuId, ?int $itemId = null): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'destination_type' => ['required', Rule::in(StoreMenuItem::DESTINATIONS)],
            'destination_id' => ['nullable', 'integer'],
            'url' => ['nullable', 'string', 'max:1000'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'parent_id' => ['nullable', 'integer'],
        ]);

        if (!empty($data['parent_id'])) {
            $parent = StoreMenuItem::where('project_id', $projectId)->where('store_menu_id', $menuId)->find($data['parent_id']);
            if (!$parent || $parent->id === $itemId || $parent->parent_id) {
                throw ValidationException::withMessages(['parent_id' => 'Selecciona una opción principal válida.']);
            }
        }

        if (in_array($data['destination_type'], ['category', 'subcategory'], true)) {
            $category = Category::where('project_id', $projectId)->find($data['destination_id'] ?? 0);
            if (!$category || ($data['destination_type'] === 'category' && $category->parent_id) || ($data['destination_type'] === 'subcategory' && !$category->parent_id)) {
                throw ValidationException::withMessages(['destination_id' => 'Selecciona una categoría válida de esta tienda.']);
            }
        } elseif ($data['destination_type'] === 'page') {
            if (!StorePage::where('project_id', $projectId)->whereKey($data['destination_id'] ?? 0)->exists()) {
                throw ValidationException::withMessages(['destination_id' => 'Selecciona una página válida de esta tienda.']);
            }
        } elseif ($data['destination_type'] === 'external') {
            $scheme = strtolower((string) parse_url((string) ($data['url'] ?? ''), PHP_URL_SCHEME));
            if (!filter_var($data['url'] ?? null, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                throw ValidationException::withMessages(['url' => 'La URL externa debe comenzar con http:// o https://.']);
            }
        }

        $data['destination_id'] = in_array($data['destination_type'], ['category', 'subcategory', 'page'], true)
            ? ($data['destination_id'] ?? null) : null;
        $data['url'] = $data['destination_type'] === 'external' ? $data['url'] : null;
        foreach (['is_enabled', 'show_desktop', 'show_tablet', 'show_mobile'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        return $data;
    }

    private function back(string $message)
    {
        return redirect()->route('settings.design', ['s' => 'constructor'])
            ->withFragment('constructor-navegacion')->with('success', $message);
    }
}
