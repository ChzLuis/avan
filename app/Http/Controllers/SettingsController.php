<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index(Project $project)
    {
        return view('settings.index', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'nullable|string|max:120|regex:/^[a-z0-9\-]+$/|unique:projects,slug,'.$project->id,
            'description' => 'nullable|string|max:500',
            'category'    => 'nullable|string|max:80',
            'phone'       => 'nullable|string|max:30',
            'whatsapp'    => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:200',
        ]);

        // Si el slug viene vacío o no fue enviado, regenerarlo desde el nombre
        if (empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (Project::where('slug', $slug)->where('id', '!=', $project->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        $project->update($data);
        foreach (['ruc', 'email', 'country', 'currency'] as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }
        return back()->with('success', 'Configuración guardada.');
    }

    public function modules(Project $project)
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        $allModules      = Module::where('is_active', true)->orderBy('sort_order')->get();
        $activeModuleIds = $project->modules()->wherePivot('is_active', true)->pluck('modules.id')->toArray();
        return view('settings.modules', compact('project', 'allModules', 'activeModuleIds'));
    }

    public function updateModules(Request $request, Project $project)
    {
        abort_unless(auth()->id() === $project->owner_id, 403);

        // Toggle individual desde el centro de control
        if ($request->has('module_id')) {
            $moduleId = (int) $request->input('module_id');
            $enabled  = $request->input('enabled') === '1';
            $project->modules()->syncWithoutDetaching([$moduleId => ['is_active' => $enabled]]);
            $cat = $request->input('redirect_cat', 'plataformas');
            return redirect()->route('settings.modules', ['project' => $project->id, 'cat' => $cat])
                ->with('success', $enabled ? 'Módulo activado.' : 'Módulo desactivado.');
        }

        // Sync completo (usado desde projects/panel)
        $moduleIds = $request->input('modules', []);
        $sync = [];
        foreach (Module::all() as $module) {
            $sync[$module->id] = ['is_active' => in_array($module->id, $moduleIds)];
        }
        $project->modules()->sync($sync);
        return back()->with('success', 'Módulos actualizados.');
    }

    public function design(Project $project)
    {
        return view('settings.design', compact('project'));
    }

    public function updateDesign(Request $request, Project $project)
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        $keys = [
            'primary_color','whatsapp_msg',
            'facebook_url','instagram_url','tiktok_url','youtube_url','twitter_url','linkedin_url',
            'hero_title','hero_subtitle','hero_badge','hero_bg_color',
            'banner1_title','banner1_sub','banner2_title','banner2_sub',
            'seo_title','seo_description','seo_keywords',
            'store_mode','quote_price_display','quote_whatsapp','quote_whatsapp_country','quote_wa_msg',
            // Pagos — llaves de texto plano
            'payment_yape_number','payment_plin_number','payment_bank_details','payment_manual_instructions',
            'culqi_public_key','culqi_mode',
            // Login
            'login_bg_type','login_color1','login_color2','login_bg_image','login_heading','login_subtitle',
        ];
        foreach ($request->only($keys) as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Checkboxes booleanos (on/off)
        foreach (['payment_manual_enabled', 'culqi_enabled', 'mp_enabled'] as $boolKey) {
            $project->settings()->updateOrCreate(
                ['key' => $boolKey],
                ['value' => $request->input($boolKey) === '1' ? '1' : '0']
            );
        }

        // Arrays de checkboxes
        $project->settings()->updateOrCreate(
            ['key' => 'accepted_payments'],
            ['value' => json_encode($request->input('accepted_payments', []))]
        );
        $project->settings()->updateOrCreate(
            ['key' => 'payment_manual_methods'],
            ['value' => json_encode($request->input('payment_manual_methods', []))]
        );

        // Llaves secretas: solo guardar si se envió algo que no sea la máscara
        foreach (['culqi_secret_key', 'mp_access_token'] as $secretKey) {
            $val = $request->input($secretKey, '');
            if ($val && !str_starts_with($val, '••')) {
                $project->settings()->updateOrCreate(['key' => $secretKey], ['value' => $val]);
            }
        }

        return back()->with('success', 'Configuración guardada.');
    }
}
