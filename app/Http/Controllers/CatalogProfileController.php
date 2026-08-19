<?php

namespace App\Http\Controllers;

use App\Models\StoreCatalogProfile;
use App\Storefront\StoreCatalogProfileWriteService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogProfileController extends Controller
{
    public function __construct(private readonly StoreCatalogProfileWriteService $writes)
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

    private function ownedProfile(int $id): StoreCatalogProfile
    {
        $project = $this->project();
        $profile = $project->catalogProfiles()->findOrFail($id);

        return $profile;
    }

    /** Habilita/deshabilita toda la funcionalidad para la tienda. */
    public function toggleFeature(Request $request)
    {
        $project = $this->project();
        $enabled = $request->boolean('enabled') ? '1' : '0';
        $project->settings()->updateOrCreate(['key' => 'catalog_profiles_enabled'], ['value' => $enabled]);
        // Política de productos sin perfil (opcional, con default seguro 'hide').
        if ($request->filled('orphan_policy')) {
            $policy = in_array($request->input('orphan_policy'), ['hide', 'show_all'], true) ? $request->input('orphan_policy') : 'hide';
            $project->settings()->updateOrCreate(['key' => 'catalog_profile_orphan_policy'], ['value' => $policy]);
        }

        return back()->with('status', 'Perfiles de catálogo actualizados.');
    }

    public function store(Request $request)
    {
        $project = $this->project();
        $data = $this->validated($request);
        $data = $this->handleUploads($request, $project, $data);
        $this->writes->create($project, $data);

        return back()->with('status', 'Perfil creado.');
    }

    public function update(Request $request, int $id)
    {
        $project = $this->project();
        $profile = $this->ownedProfile($id);
        $data = $this->validated($request, $profile->id);
        $data = $this->handleUploads($request, $project, $data);
        $this->writes->update($project, $profile, $data);

        return back()->with('status', 'Perfil actualizado.');
    }

    public function destroy(int $id)
    {
        $project = $this->project();
        $this->writes->delete($project, $this->ownedProfile($id));

        return back()->with('status', 'Perfil eliminado.');
    }

    public function reorder(Request $request)
    {
        $project = $this->project();
        $this->writes->reorder($project, (array) $request->input('order', []));

        return response()->json(['ok' => true]);
    }

    /** Accesos rápidos: crea un par de perfiles editables (Hombre/Mujer, etc.). */
    public function quickCreate(Request $request)
    {
        $project = $this->project();
        $preset = (string) $request->input('preset', '');
        $presets = [
            'moda' => ['Hombre', 'Mujer'],
            'infantil' => ['Niño', 'Niña'],
            'tecnologia' => ['Gamer', 'Oficina'],
            'ventas' => ['Minorista', 'Mayorista'],
        ];
        abort_unless(isset($presets[$preset]), 422);
        foreach ($presets[$preset] as $name) {
            $this->writes->create($project, ['name' => $name]);
        }

        return back()->with('status', 'Perfiles creados. Edítalos para asignar productos e identidad.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $project = app('active_project');
        $colorRule = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('store_catalog_profiles', 'slug')
                    ->where('project_id', $project->id)
                    ->ignore($ignoreId)],
            'menu_label' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'primary_color' => $colorRule,
            'secondary_color' => $colorRule,
            'header_bg_color' => $colorRule,
            'header_text_color' => $colorRule,
            'button_color' => $colorRule,
            'footer_bg_color' => $colorRule,
            'announcement_bg_color' => $colorRule,
            'hero_title' => ['nullable', 'string', 'max:200'],
            'hero_description' => ['nullable', 'string', 'max:500'],
            // AVIF entra en la lista: los exportadores modernos lo generan por defecto y
            // el archivo se rechazaba sin explicacion visible en el formulario.
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg,avif', 'max:8192'],
            'mobile_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg,avif', 'max:8192'],
            'favicon' => ['nullable', 'image', 'mimes:png,webp,svg,ico,avif', 'max:1024'],
            'hero_desktop' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:6144'],
            'hero_mobile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:6144'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
        ]);
    }

    /** Sube imágenes del perfil (mismo mecanismo que el resto del Diseñador). */
    private function handleUploads(Request $request, $project, array $data): array
    {
        // Cada hueco pide un encuadre distinto: el logo se conserva entero y
        // con su transparencia, mientras que los hero llenan su franja.
        $fileMap = [
            'logo' => ['logo_path', 'logo'],
            'mobile_logo' => ['mobile_logo_path', 'logo'],
            'favicon' => ['favicon_path', 'logo'],
            'hero_desktop' => ['hero_desktop_path', 'banner'],
            'hero_mobile' => ['hero_mobile_path', 'banner_movil'],
        ];
        foreach ($fileMap as $input => [$column, $perfil]) {
            if ($request->hasFile($input)) {
                $data[$column] = \App\Support\Imagen\ProcesadorImagenes::ruta(
                    $request->file($input), "catalog-profiles/{$project->id}", $perfil
                );
            }
            unset($data[$input]);
        }

        return $data;
    }
}
