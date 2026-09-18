<?php

namespace App\Modules\Tienda\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Tienda\Models\StoreMenuItem;
use App\Modules\Tienda\Storefront\StoreMenuWriteService;
use App\Modules\Tienda\Support\StorefrontNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreNavigationController extends Controller
{
    public function __construct(private readonly StoreMenuWriteService $menuWrites)
    {
    }

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
            'header_logo_height' => ['required', 'integer', 'min:20', 'max:300'],
            'header_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'header_mobile_style' => ['required', Rule::in(['drawer', 'compact'])],
            'header_tablet_style' => ['required', Rule::in(['drawer', 'desktop'])],
        ]);

        if ($request->hasFile('header_logo')) {
            $data['header_logo_url'] = \App\Support\Imagen\ProcesadorImagenes::ruta(
                $request->file('header_logo'), "store-header/{$project->id}", 'logo'
            );
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
        $data = $this->validateItem($request);
        $this->menuWrites->createItem($project, $menu, $data);
        return $this->back('Opción agregada al menú.');
    }

    public function updateItem(Request $request, int $item)
    {
        $project = $this->project();
        $menu = StorefrontNavigation::ensure($project);
        $data = $this->validateItem($request);
        $this->menuWrites->updateItem($project, $menu, $item, $data);
        return $this->back('Opción del menú actualizada.');
    }

    public function destroyItem(int $item)
    {
        $project = $this->project();
        $menu = StorefrontNavigation::ensure($project);
        $this->menuWrites->deleteItem($project, $menu, $item);
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
        $this->menuWrites->reorder($project, $menu, $data['items']);
        return response()->json(['ok' => true, 'message' => 'Orden del menú guardado.']);
    }

    /**
     * Valida el request de un ítem (formato). La propiedad del destino y la
     * normalización canónica las aplica StoreMenuWriteService.
     */
    private function validateItem(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'destination_type' => ['required', Rule::in(StoreMenuItem::DESTINATIONS)],
            'destination_id' => ['nullable', 'integer'],
            'url' => ['nullable', 'string', 'max:1000'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'parent_id' => ['nullable', 'integer'],
        ]);
        foreach (['is_enabled', 'show_desktop', 'show_tablet', 'show_mobile'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        return $data;
    }

    private function back(string $message)
    {
        return redirect()->route('settings.builder')
            ->withFragment('header')->with('success', $message);
    }
}
