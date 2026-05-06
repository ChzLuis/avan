<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Module;
use App\Models\Coupon;
use App\Models\WaCanal;
use App\Support\CatalogTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $isSuperadmin = auth()->user()->is_superadmin ?? false;

        $projects = $isSuperadmin
            ? Project::orderBy('name')->get()
            : Project::where('owner_id', $userId)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $userId))
                ->orderBy('name')
                ->get();

        // Si viene ?p=ID, mostrar ese proyecto en el panel derecho
        if ($request->p && $pid = (int) $request->p) {
            $project = $isSuperadmin
                ? Project::findOrFail($pid)
                : ($projects->firstWhere('id', $pid) ?? app('active_project'));
        } else {
            $project = app('active_project');
        }

        return view('settings.index', compact('project', 'projects'));
    }

    public function update(Request $request)
    {
        $userId = auth()->id();
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        // Permitir editar cualquier proyecto del usuario, no solo el activo
        if ($request->input('project_id')) {
            $project = $isSuperadmin
                ? Project::findOrFail($request->input('project_id'))
                : Project::where('owner_id', $userId)->findOrFail($request->input('project_id'));
        } else {
            /** @var \App\Models\Project $project */
            $project = app('active_project');
            $this->authorizeProject($project);
        }
        // Sección wa_phone — guardado directo
        if ($request->input('section') === 'wa_phone') {
            $project->update(['wa_phone' => preg_replace('/\D/', '', $request->input('wa_phone', '')) ?: null]);
            return back()->with('success', 'Número del bot guardado.');
        }

        // Sección show_wa_button
        if ($request->input('section') === 'show_wa_button') {
            $project->settings()->updateOrCreate(['key' => 'show_wa_button'], ['value' => $request->boolean('show_wa_button') ? '1' : '0']);
            return back()->with('success', 'Configuración guardada.');
        }

        $customDomainRule = $isSuperadmin
            ? 'nullable|string|max:150|unique:projects,custom_domain,'.$project->id
            : 'prohibited';

        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:100',
            'slug'          => 'nullable|string|max:120|regex:/^[a-z0-9\-]+$/|unique:projects,slug,'.$project->id,
            'description'   => 'nullable|string|max:500',
            'category'      => 'nullable|string|max:80',
            'phone'         => 'nullable|string|max:30',
            'whatsapp'      => 'nullable|string|max:30',
            'address'       => 'nullable|string|max:200',
            'custom_domain' => $customDomainRule,
        ]);

        // Normalizar custom_domain
        if (isset($data['custom_domain'])) {
            $data['custom_domain'] = $data['custom_domain']
                ? strtolower(trim($data['custom_domain']))
                : null;
        }

        // Si el slug viene vacío o no fue enviado, regenerarlo desde el nombre
        if (!empty($data['name']) && empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (Project::where('slug', $slug)->where('id', '!=', $project->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        if (!empty($data)) {
            $project->update($data);
        }

        $settingsKeys = [
            'ruc', 'razon_social', 'email', 'country', 'currency', 'sunat_url', 'sunat_url_prod',
            'nubefact_url', 'nubefact_token', 'serie_factura', 'serie_boleta', 'apiperu_token',
            // Redes sociales
            'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'twitter_url', 'linkedin_url',
            // SEO
            'seo_title', 'seo_description', 'seo_keywords',
            // Envío
            'shipping_enabled', 'shipping_cost', 'shipping_free_from', 'require_address',
        ];
        foreach ($settingsKeys as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }
        return redirect()
            ->route('settings', ['p' => $project->id, 's' => $request->input('_tab', 'datos')])
            ->with('success', 'Configuración guardada.');
    }

    public function seo(Request $request)
    {
        $userId = auth()->id();
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        $projects = $isSuperadmin
            ? Project::orderBy('name')->get()
            : Project::where('owner_id', $userId)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $userId))
                ->orderBy('name')->get();
        if ($request->p && $pid = (int) $request->p) {
            $project = $projects->firstWhere('id', $pid) ?? app('active_project');
        } else {
            $project = app('active_project');
        }
        return view('settings.seo', compact('project', 'projects'));
    }

    public function updateSeo(Request $request)
    {
        $userId = auth()->id();
        if ($request->input('project_id')) {
            $project = $isSuperadmin
                ? Project::findOrFail($request->input('project_id'))
                : Project::where('owner_id', $userId)->findOrFail($request->input('project_id'));
        } else {
            $project = app('active_project');
            $this->authorizeProject($project);
        }
        $seoKeys = [
            'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical',
            'og_title', 'og_description', 'og_image',
            'ga_id', 'gtm_id', 'fb_pixel_id', 'tiktok_pixel_id',
            'robots', 'sitemap_enabled',
            'schema_type', 'schema_price_range', 'schema_opening_hours',
            'google_site_verification', 'bing_site_verification',
        ];
        foreach ($seoKeys as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key) ?? '']);
            }
        }
        return redirect()
            ->route('settings.seo', ['p' => $project->id, 's' => $request->input('_tab', 'basico')])
            ->with('success', 'SEO guardado.');
    }

    public function modules(Request $request)
    {
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        $userId = auth()->id();

        if ($request->p && $isSuperadmin) {
            $project = Project::findOrFail((int) $request->p);
        } else {
            $project = app('active_project');
            $this->authorizeProject($project);
        }

        $allModules      = Module::where('is_active', true)->orderBy('sort_order')->get();
        $activeModuleIds = $project->modules()->wherePivot('is_active', true)->pluck('modules.id')->toArray();
        return view('settings.modules', compact('project', 'allModules', 'activeModuleIds'));
    }

    public function updateModules(Request $request)
    {
        $isSuperadmin = auth()->user()->is_superadmin ?? false;
        $userId = auth()->id();

        if ($request->p && $isSuperadmin) {
            $project = Project::findOrFail((int) $request->p);
        } else {
            $project = app('active_project');
            $this->authorizeProject($project);
        }

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

    public function design()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        return view('settings.design', compact('project'));
    }

    public function uploadLogo(Request $request)
    {
        $project = app('active_project');
        $this->authorizeProject($project);
        $request->validate(['file' => 'required|image|max:2048']);
        $type = $request->input('type', 'logo');
        $path = $request->file('file')->store("logos/{$project->id}", 'public');
        return response()->json([
            'path' => $path,
            'url'  => asset('storage/' . $path),
        ]);
    }

    public function importLogs()
    {
        $project = app('active_project');
        $logs = ImportLog::where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($l) => [
                'id'         => $l->id,
                'type'       => $l->type,
                'filename'   => $l->filename,
                'created'    => $l->created,
                'updated'    => $l->updated,
                'skipped'    => $l->skipped,
                'has_errors' => $l->has_errors,
                'errors'     => $l->errors ?? [],
                'date'       => $l->created_at->format('d/m/Y H:i'),
                'date_diff'  => $l->created_at->diffForHumans(),
            ]);
        return response()->json(['logs' => $logs]);
    }

    public function updateDesign(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $keys = [
            // Marca
            'primary_color','secondary_color','whatsapp_msg',
            'logo_url','logo_height','favicon_url',
            'font_title','font_body','border_radius','currency_symbol',
            'facebook_url','instagram_url','tiktok_url','youtube_url','twitter_url','linkedin_url',
            // Portada — Hero
            'hero_title','hero_subtitle','hero_badge','hero_bg_color',
            'hero_image','hero_overlay','hero_align','hero_height',
            'hero_cta1_show','hero_cta1_text','hero_cta2_show','hero_cta2_text',
            // Portada — Banners y extras
            'banner1_title','banner1_sub','banner2_title','banner2_sub',
            'announcement_text','announcement_bg',
            'countdown_label','countdown_end',
            'split_left_title','split_left_sub','split_right_title','split_right_sub',
            'trust_icon_1','trust_text_1','trust_icon_2','trust_text_2','trust_icon_3','trust_text_3',
            'tab1_label','tab2_label','tab3_label',
            // Catálogo — Grid
            'catalog_section_title','card_style','catalog_cols_desktop','catalog_cols_mobile',
            'catalog_filter_price','catalog_filter_cats','catalog_filter_sale','catalog_filter_search',
            'catalog_badge_sale','catalog_badge_new','catalog_badge_featured','catalog_badge_sold_out',
            'catalog_show_ratings','catalog_quick_view','catalog_show_sku','catalog_show_stock','wholesale_enabled',
            // Catálogo — Botones y flotantes
            'btn_cart_text','btn_quote_text','btn_shape','btn_show_icon',
            'float_cart_show','float_cart_pos','float_wa_show','float_wa_tooltip','float_wa_pos',
            // Sistema — Venta
            'store_mode','quote_price_display','quote_whatsapp','quote_whatsapp_country','quote_wa_msg',
            // Sistema — Pagos
            'payment_yape_number','payment_plin_number','payment_bank_details','payment_manual_instructions',
            'culqi_public_key','culqi_mode',
            // Sistema — Footer y textos
            'footer_tagline','footer_copyright',
            'footer_show_social','footer_show_payment','footer_show_address',
            'cart_title','cart_empty_msg','btn_checkout_text','btn_send_quote_text',
            'txt_no_results','txt_search_placeholder','txt_view_more','txt_all_cats',
            // Sistema — Login
            'login_bg_type','login_color1','login_color2','login_bg_image','login_heading','login_subtitle',
            // Sistema — SEO
            'seo_title','seo_description','seo_keywords',
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

    public function payments()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        return view('settings.payments', compact('project'));
    }

    public function updatePayments(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $keys = [
            'payment_yape_number','payment_plin_number',
            'payment_bank_details','payment_manual_instructions',
            'culqi_public_key','culqi_mode',
            'store_mode','quote_price_display',
            'quote_whatsapp','quote_whatsapp_country','quote_wa_msg',
        ];
        foreach ($request->only($keys) as $key => $value) {
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        foreach (['payment_manual_enabled', 'culqi_enabled', 'mp_enabled'] as $boolKey) {
            $project->settings()->updateOrCreate(
                ['key' => $boolKey],
                ['value' => $request->input($boolKey) === '1' ? '1' : '0']
            );
        }

        $project->settings()->updateOrCreate(
            ['key' => 'accepted_payments'],
            ['value' => json_encode($request->input('accepted_payments', []))]
        );
        $project->settings()->updateOrCreate(
            ['key' => 'payment_manual_methods'],
            ['value' => json_encode($request->input('payment_manual_methods', []))]
        );

        foreach (['culqi_secret_key', 'mp_access_token'] as $secretKey) {
            $val = $request->input($secretKey, '');
            if ($val && !str_starts_with($val, '••')) {
                $project->settings()->updateOrCreate(['key' => $secretKey], ['value' => $val]);
            }
        }

        return back()->with('success', 'Configuración de pagos guardada.');
    }

    public function applyTemplate(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $templateKey = $request->input('template');
        $template    = CatalogTemplates::get($templateKey);

        if (!$template) {
            return response()->json(['ok' => false, 'message' => 'Plantilla no encontrada.'], 422);
        }

        // Guardar la clave de plantilla activa
        $project->settings()->updateOrCreate(['key' => 'catalog_template'], ['value' => $templateKey]);

        // Aplicar todos los settings de la plantilla (sin sobrescribir llaves secretas)
        $skip = ['culqi_secret_key', 'mp_access_token', 'culqi_public_key'];
        foreach ($template['settings'] as $key => $value) {
            if (in_array($key, $skip)) continue;
            $project->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Crear categorías y subcategorías predefinidas de la plantilla
        CatalogTemplates::applyCategories($project, $templateKey);

        return response()->json(['ok' => true, 'template' => $templateKey]);
    }

    public function storeCoupon(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $data = $request->validate([
            'code'       => 'required|string|max:50',
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0.01',
            'min_order'  => 'nullable|numeric|min:0',
            'max_uses'   => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);
        $data['code']      = strtoupper(trim($data['code']));
        $data['min_order'] = $data['min_order'] ?? 0;
        $coupon = $project->coupons()->updateOrCreate(
            ['code' => $data['code']],
            array_merge($data, ['is_active' => true, 'uses_count' => 0])
        );
        return response()->json(['ok' => true, 'coupon' => $coupon]);
    }

    public function destroyCoupon(int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $project->coupons()->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    public function toggleCoupon(int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $coupon = $project->coupons()->findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);
        return response()->json(['ok' => true, 'is_active' => $coupon->is_active]);
    }

    public function storeCanal(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_if(!$project, 403);

        $data = $request->validate([
            'id'                 => 'nullable|integer',
            'nombre'             => 'required|string|max:80',
            'telefono'           => 'nullable|string|max:30',
            'phone_number_id'    => 'nullable|string|max:80',
            'access_token'       => 'nullable|string',
            'verify_token'       => 'nullable|string|max:100',
            'mensaje_bienvenida' => 'nullable|string|max:500',
            'mensaje_ausencia'   => 'nullable|string|max:500',
        ]);

        if (!empty($data['id'])) {
            $canal = WaCanal::where('project_id', $project->id)->findOrFail($data['id']);
            if (empty($data['access_token'])) unset($data['access_token']);
            if (empty($data['verify_token']))  unset($data['verify_token']);
            $canal->update($data);
        } else {
            $canal = WaCanal::create(array_merge($data, [
                'project_id' => $project->id,
                'tipo'       => 'bixo',
                'activo'     => true,
            ]));
        }

        return response()->json(['ok' => true, 'canal' => $canal->makeVisible(['access_token', 'verify_token'])]);
    }

    public function destroyCanal(WaCanal $canal)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($canal->project_id === $project->id, 403);
        $canal->delete();
        return response()->json(['ok' => true]);
    }
}
