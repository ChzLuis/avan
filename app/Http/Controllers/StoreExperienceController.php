<?php

namespace App\Http\Controllers;

use App\Models\StorePage;
use App\Models\StorePopup;
use App\Models\StoreSection;
use App\Storefront\StoreSectionWriteService;
use App\Storefront\StorePageWriteService;
use App\Storefront\StorePopupWriteService;
use App\Support\StorefrontSections;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreExperienceController extends Controller
{
    public function __construct(
        private readonly StoreSectionWriteService $sectionWrites,
        private readonly StorePageWriteService $pageWrites,
        private readonly StorePopupWriteService $popupWrites,
    ) {
    }

    private function project()
    {
        return app('active_project');
    }

    private function redirectToBuilder(string $message, string $fragment = 'home')
    {
        return redirect()->route('settings.builder')
            ->withFragment($fragment)
            ->with('success', $message);
    }

    public function index()
    {
        return redirect()->route('settings.builder')->withFragment('home');
    }

    public function saveHomeSection(Request $request, string $component)
    {
        abort_unless(array_key_exists($component, StorefrontSections::COMPONENTS), 404);
        $project = $this->project();
        $this->sectionWrites->ensureHomeSections($project);
        $section = $project->storeSections()->where('page', 'home')->where('component', $component)->firstOrFail();
        $action = $request->input('action', 'draft');

        if ($action === 'reset') {
            $default = StorefrontSections::definition($project, $component);
            $this->sectionWrites->saveDraft($project, 'home', $component, [
                'content' => $default['content'],
                'variant' => $default['variant'],
                'is_enabled' => $default['enabled'],
                'sort_order' => $section->sort_order,
            ]);
            return $this->redirectToBuilder(
                'Valores predeterminados restaurados como borrador. Revisa la vista previa antes de publicar.',
                'home-section-'.$component
            );
        }

        $data = $request->validate($this->rulesFor($component, $project->id));
        $oldContent = $section->contentForPreview();
        $content = $this->mergeImages($request, $component, $data['content'] ?? [], $oldContent);
        $variant = $this->variantFor($component, $content);
        $enabled = $request->boolean('is_enabled');
        $sortOrder = max(0, (int) ($data['sort_order'] ?? $section->draft_sort_order ?? $section->sort_order));

        if ($component === 'daily_offer' && $request->boolean('content.quick_24')) {
            $content['ends_at'] = now()->addDay()->format('Y-m-d\TH:i');
        }
        unset($content['quick_24']);

        $changes = [
            'variant' => $variant,
            'content' => $content,
            'sort_order' => $sortOrder,
            'is_enabled' => $enabled,
            'show_desktop' => $request->boolean('show_desktop'),
            'show_tablet' => $request->boolean('show_tablet'),
            'show_mobile' => $request->boolean('show_mobile'),
            'publish_from' => $data['publish_from'] ?? null,
            'publish_until' => $data['publish_until'] ?? null,
        ];

        if ($action === 'publish') {
            $this->sectionWrites->saveAndPublish($project, 'home', $component, $changes);
            return $this->redirectToBuilder(
                StorefrontSections::COMPONENTS[$component].' publicado correctamente.',
                'home-section-'.$component
            );
        }

        $this->sectionWrites->saveDraft($project, 'home', $component, $changes);

        // Constructor guiado: misma validación y servicio, respuesta JSON.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'component' => $component, 'action' => $action]);
        }

        return $this->redirectToBuilder(
            'Borrador guardado. La tienda publicada no cambió.',
            'home-section-'.$component
        );
    }

    public function reorderHome(Request $request)
    {
        $project = $this->project();
        $this->sectionWrites->ensureHomeSections($project);
        $data = $request->validate([
            'order' => ['required', 'array', 'size:'.count(StorefrontSections::COMPONENTS)],
            'order.*' => ['required', 'integer', 'distinct'],
        ]);
        $this->sectionWrites->reorderDraft($project, 'home', $data['order']);
        return $this->redirectToBuilder('Nuevo orden guardado como borrador.');
    }

    /**
     * Guarda SÓLO el estado de una sección (activar, visibilidad por dispositivo
     * y orden), sin re-enviar su contenido. Lo usa el nuevo Diseñador visual.
     * Usa el servicio canónico (StoreSectionWriteService), sin lógica nueva de
     * escritura. Devuelve JSON.
     */
    public function sectionState(Request $request, string $component)
    {
        abort_unless(array_key_exists($component, StorefrontSections::COMPONENTS), 404);
        $project = $this->project();
        $this->sectionWrites->ensureHomeSections($project);

        $data = $request->validate([
            'action'       => ['nullable', Rule::in(['draft', 'publish'])],
            'is_enabled'   => ['required', 'boolean'],
            'show_desktop' => ['required', 'boolean'],
            'show_mobile'  => ['required', 'boolean'],
            'show_tablet'  => ['nullable', 'boolean'],
            'sort_order'   => ['required', 'integer', 'min:0'],
        ]);

        $changes = [
            'is_enabled'   => $request->boolean('is_enabled'),
            'show_desktop' => $request->boolean('show_desktop'),
            'show_mobile'  => $request->boolean('show_mobile'),
            'show_tablet'  => $request->boolean('show_tablet', $request->boolean('show_mobile')),
            'sort_order'   => (int) $data['sort_order'],
        ];

        $section = ($data['action'] ?? 'draft') === 'publish'
            ? $this->sectionWrites->saveAndPublish($project, 'home', $component, $changes)
            : $this->sectionWrites->saveDraft($project, 'home', $component, $changes);

        return response()->json(['ok' => true, 'component' => $component, 'id' => $section->id]);
    }

    public function publishAll(Request $request)
    {
        $project = $this->project();
        $published = $this->sectionWrites->publishAll($project, 'home');
        $message = $published === 1
            ? 'Se publicó 1 borrador.'
            : "Se publicaron {$published} borradores.";

        return $this->redirectToBuilder($message);
    }

    public function publishOne(Request $request, string $component)
    {
        abort_unless(array_key_exists($component, StorefrontSections::COMPONENTS), 404);
        $published = $this->sectionWrites->publishOne($this->project(), 'home', $component);

        return $this->redirectToBuilder(
            $published ? StorefrontSections::COMPONENTS[$component].' publicado correctamente.' : 'La sección no tiene un borrador pendiente.',
            'home-section-'.$component,
        );
    }

    public function discardDraft(Request $request, string $component)
    {
        abort_unless(array_key_exists($component, StorefrontSections::COMPONENTS), 404);
        $discarded = $this->sectionWrites->discardDraft($this->project(), 'home', $component);

        return $this->redirectToBuilder(
            $discarded ? 'Borrador descartado. La versión publicada se conserva.' : 'La sección no tiene un borrador pendiente.',
            'home-section-'.$component,
        );
    }

    public function preview()
    {
        $project = $this->project();
        return app(PublicController::class)->previewStorefront($project);
    }

    private function rulesFor(string $component, int $projectId): array
    {
        $rules = [
            'action' => ['nullable', Rule::in(['draft', 'publish'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
            'show_desktop' => ['nullable', 'boolean'],
            'show_tablet' => ['nullable', 'boolean'],
            'show_mobile' => ['nullable', 'boolean'],
            'publish_from' => ['nullable', 'date'],
            'publish_until' => ['nullable', 'date', 'after_or_equal:publish_from'],
            'content' => ['required', 'array'],
        ];
        $text = ['nullable', 'string', 'max:500'];
        $url = ['nullable', 'string', 'max:500'];
        $image = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:6144'];

        $specific = match ($component) {
            'hero' => [
                'content.mode' => ['required', Rule::in(['single', 'slider'])],
                'content.autoplay' => ['nullable', 'boolean'], 'content.interval' => ['nullable', 'integer', 'min:3', 'max:30'],
                'content.single' => ['required', 'array'], 'content.single.title' => ['nullable', 'string', 'max:180'],
                'content.single.body' => ['nullable', 'string', 'max:1000'], 'content.single.desktop_image' => $url,
                'content.single.mobile_image' => $url, 'content.single.desktop_upload' => $image, 'content.single.mobile_upload' => $image,
                'content.single.primary_text' => $text, 'content.single.primary_url' => $url,
                'content.single.primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'content.single.secondary_text' => $text, 'content.single.secondary_url' => $url,
                'content.single.secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'content.slides' => ['nullable', 'array', 'max:12'], 'content.slides.*.key' => ['nullable', 'string', 'max:60'],
                'content.slides.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.slides.*.enabled' => ['nullable', 'boolean'], 'content.slides.*.title' => ['nullable', 'string', 'max:180'],
                'content.slides.*.body' => ['nullable', 'string', 'max:1000'], 'content.slides.*.desktop_image' => $url,
                'content.slides.*.mobile_image' => $url, 'content.slides.*.desktop_upload' => $image, 'content.slides.*.mobile_upload' => $image,
                'content.slides.*.primary_text' => $text, 'content.slides.*.primary_url' => $url,
                'content.slides.*.primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'content.slides.*.secondary_text' => $text, 'content.slides.*.secondary_url' => $url,
                'content.slides.*.secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            'benefits' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.body' => ['nullable', 'string', 'max:1000'],
                'content.items' => ['required', 'array', 'max:4'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.title' => ['required', 'string', 'max:120'],
                'content.items.*.description' => ['nullable', 'string', 'max:500'], 'content.items.*.icon' => ['nullable', 'string', 'max:40'],
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image,
            ],
            'announcements' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.quantity' => ['required', 'integer', 'min:1', 'max:4'],
                'content.items' => ['nullable', 'array', 'max:4'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.title' => ['nullable', 'string', 'max:180'],
                'content.items.*.description' => ['nullable', 'string', 'max:1000'], 'content.items.*.image' => $url,
                'content.items.*.image_upload' => $image, 'content.items.*.url' => $url, 'content.items.*.button_text' => $text,
                'content.items.*.starts_at' => ['nullable', 'date'], 'content.items.*.ends_at' => ['nullable', 'date'],
            ],
            'featured_categories' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.display' => ['required', Rule::in(['icons', 'images', 'buttons'])],
                'content.limit' => ['required', 'integer', 'min:1', 'max:12'], 'content.category_ids' => ['nullable', 'array', 'max:20'],
                'content.category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('project_id', $projectId)],
            ],
            'daily_offer' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.body' => ['nullable', 'string', 'max:1000'],
                'content.ends_at' => ['nullable', 'date'], 'content.quick_24' => ['nullable', 'boolean'], 'content.image' => $url,
                'content.image_upload' => $image, 'content.background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'content.button_text' => $text, 'content.button_url' => $url,
                'content.expired_action' => ['required', Rule::in(['hide', 'message'])], 'content.expired_message' => $text,
                'content.subtitle' => $text, 'content.count' => ['nullable', 'integer', 'min:1', 'max:8'],
                'content.product_ids' => ['nullable', 'array', 'max:8'],
                'content.product_ids.*' => ['integer', Rule::exists('products', 'id')->where('project_id', $projectId)],
            ],
            'discounts' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.limit' => ['required', 'integer', 'min:1', 'max:24'],
                'content.layout' => ['required', Rule::in(['grid', 'carousel'])], 'content.columns_desktop' => ['required', 'integer', 'min:1', 'max:6'],
                'content.columns_tablet' => ['required', 'integer', 'min:1', 'max:4'], 'content.columns_mobile' => ['required', 'integer', 'min:1', 'max:2'],
                'content.selection' => ['required', Rule::in(['automatic', 'manual'])], 'content.product_ids' => ['nullable', 'array', 'max:50'],
                'content.product_ids.*' => ['integer', Rule::exists('products', 'id')->where('project_id', $projectId)],
                'content.show_old_price' => ['nullable', 'boolean'], 'content.show_current_price' => ['nullable', 'boolean'],
                'content.show_percentage' => ['nullable', 'boolean'],
            ],
            'featured_products' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.limit' => ['required', 'integer', 'min:1', 'max:24'],
                'content.badge' => ['nullable', 'string', 'max:80'], 'content.description' => ['nullable', 'string', 'max:300'],
                'content.selection' => ['required', Rule::in(['automatic', 'manual', 'newest', 'discounted', 'category'])], 'content.product_ids' => ['nullable', 'array', 'max:50'],
                'content.category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('project_id', $projectId)],
                'content.product_ids.*' => ['integer', Rule::exists('products', 'id')->where('project_id', $projectId)],
                'content.show_arrows' => ['nullable', 'boolean'], 'content.allow_swipe' => ['nullable', 'boolean'],
                'content.autoplay' => ['nullable', 'boolean'], 'content.autoplay_seconds' => ['nullable', 'integer', 'min:3', 'max:30'],
            ],
            'blog' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.limit' => ['required', 'integer', 'min:2', 'max:3'],
                'content.all_text' => $text, 'content.all_url' => $url, 'content.items' => ['nullable', 'array', 'max:6'],
                'content.items.*.key' => ['nullable', 'string', 'max:60'], 'content.items.*.enabled' => ['nullable', 'boolean'],
                'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image,
                'content.items.*.tag' => ['nullable', 'string', 'max:80'], 'content.items.*.title' => ['nullable', 'string', 'max:180'],
                'content.items.*.summary' => ['nullable', 'string', 'max:1000'], 'content.items.*.date' => ['nullable', 'date'],
                'content.items.*.button_text' => $text, 'content.items.*.url' => $url,
            ],
            'media_banner' => [
                'content.media_type' => ['required', Rule::in(['image', 'video_file', 'video_url'])],
                'content.desktop_image' => $url, 'content.mobile_image' => $url, 'content.fallback_image' => $url,
                'content.desktop_upload' => $image, 'content.mobile_upload' => $image, 'content.fallback_upload' => $image,
                'content.video_file' => $url,
                'content.video_upload' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:51200'],
                'content.video_url' => $url,
                'content.autoplay' => ['nullable', 'boolean'], 'content.muted' => ['nullable', 'boolean'],
                'content.loop' => ['nullable', 'boolean'], 'content.show_controls' => ['nullable', 'boolean'],
                'content.overlay_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'content.overlay_opacity' => ['nullable', 'integer', 'min:0', 'max:90'],
                'content.height' => ['nullable', Rule::in(['small', 'medium', 'large', 'full'])],
                'content.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.button_text' => $text, 'content.button_url' => $url,
                'content.button2_text' => $text, 'content.button2_url' => $url,
            ],
            'collection_showcase' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['ambient', 'circles', 'mosaic', 'banners'])],
                'content.columns' => ['nullable', 'integer', 'min:2', 'max:4'], 'content.show_count' => ['nullable', 'boolean'],
                'content.items' => ['nullable', 'array', 'max:8'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.title' => ['nullable', 'string', 'max:120'], 'content.items.*.subtitle' => ['nullable', 'string', 'max:200'],
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image,
                'content.items.*.url' => $url,
                'content.items.*.category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('project_id', $projectId)],
            ],
            'brands' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['strip', 'grid', 'carousel'])],
                'content.grayscale' => ['nullable', 'boolean'],
                'content.items' => ['nullable', 'array', 'max:16'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.name' => ['nullable', 'string', 'max:80'],
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image, 'content.items.*.url' => $url,
            ],
            'testimonials' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['cards', 'carousel', 'band'])],
                'content.source' => ['nullable', Rule::in(['manual', 'reviews'])],
                'content.limit' => ['nullable', 'integer', 'min:1', 'max:9'],
                'content.items' => ['nullable', 'array', 'max:9'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.name' => ['nullable', 'string', 'max:80'], 'content.items.*.role' => ['nullable', 'string', 'max:120'],
                'content.items.*.text' => ['nullable', 'string', 'max:600'], 'content.items.*.rating' => ['nullable', 'integer', 'min:0', 'max:5'],
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image,
            ],
            'gallery' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['grid', 'mosaic'])],
                'content.columns' => ['nullable', 'integer', 'min:2', 'max:4'],
                'content.items' => ['nullable', 'array', 'max:12'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.type' => ['nullable', Rule::in(['image', 'video_url'])],
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image,
                'content.items.*.video_url' => $url, 'content.items.*.caption' => ['nullable', 'string', 'max:160'],
            ],
            'faq' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['accordion', 'two-columns'])],
                'content.items' => ['nullable', 'array', 'max:12'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.question' => ['nullable', 'string', 'max:200'], 'content.items.*.answer' => ['nullable', 'string', 'max:1200'],
            ],
            'wa_advisory' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['band', 'card'])],
                'content.button_text' => $text, 'content.message' => ['nullable', 'string', 'max:500'],
                'content.phone' => ['nullable', 'string', 'max:20'],
            ],
            'locations' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['cards', 'map-side'])],
                'content.items' => ['nullable', 'array', 'max:8'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.name' => ['nullable', 'string', 'max:120'], 'content.items.*.address' => ['nullable', 'string', 'max:300'],
                'content.items.*.phone' => ['nullable', 'string', 'max:30'], 'content.items.*.hours' => ['nullable', 'string', 'max:160'],
                'content.items.*.show_map' => ['nullable', 'boolean'],
            ],
            'cta_banner' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['wide', 'split'])],
                'content.image' => $url, 'content.image_upload' => $image,
                'content.button_text' => $text, 'content.button_url' => $url,
                'content.background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            'info_strip' => [
                'content.title' => ['nullable', 'string', 'max:180'], 'content.subtitle' => ['nullable', 'string', 'max:500'],
                'content.variant' => ['nullable', Rule::in(['cards', 'icons'])],
                'content.items' => ['nullable', 'array', 'max:4'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.title' => ['nullable', 'string', 'max:80'], 'content.items.*.description' => ['nullable', 'string', 'max:160'],
                'content.items.*.icon' => ['nullable', 'string', 'max:30'], 'content.items.*.url' => $url,
                'content.items.*.image' => $url, 'content.items.*.image_upload' => $image,
            ],
            'about_preview' => [
                'content.label' => ['nullable', 'string', 'max:60'], 'content.title' => ['nullable', 'string', 'max:180'],
                'content.body' => ['nullable', 'string', 'max:1200'],
                'content.variant' => ['nullable', Rule::in(['image-left', 'image-right'])],
                'content.image' => $url, 'content.image_upload' => $image,
                'content.button_text' => $text, 'content.button_url' => $url,
                'content.items' => ['nullable', 'array', 'max:6'], 'content.items.*.key' => ['nullable', 'string', 'max:60'],
                'content.items.*.enabled' => ['nullable', 'boolean'], 'content.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'content.items.*.icon' => ['nullable', 'string', 'max:30'], 'content.items.*.value' => ['nullable', 'string', 'max:30'],
                'content.items.*.title' => ['nullable', 'string', 'max:80'],
            ],
        };

        return array_merge($rules, $specific);
    }

    private function mergeImages(Request $request, string $component, array $content, array $old): array
    {
        $store = fn ($file) => $file->store('store-sections/' . $this->project()->id, 'public');

        if ($component === 'hero') {
            foreach (['desktop', 'mobile'] as $device) {
                $path = "content.single.{$device}_upload";
                $content['single']["{$device}_image"] = $request->hasFile($path)
                    ? $store($request->file($path))
                    : ($content['single']["{$device}_image"] ?? data_get($old, "single.{$device}_image"));
                unset($content['single']["{$device}_upload"]);
            }
            $content['slides'] = $this->mergeItemImages($request, $content['slides'] ?? [], $old['slides'] ?? [], ['desktop', 'mobile'], $store, 'slides');
        } elseif (in_array($component, ['benefits', 'announcements', 'blog', 'collection_showcase', 'brands', 'testimonials', 'gallery', 'info_strip'], true)) {
            $content['items'] = $this->mergeItemImages($request, $content['items'] ?? [], $old['items'] ?? [], [''], $store, 'items');
        } elseif (in_array($component, ['daily_offer', 'cta_banner', 'about_preview'], true)) {
            $content['image'] = $request->hasFile('content.image_upload')
                ? $store($request->file('content.image_upload'))
                : ($content['image'] ?? $old['image'] ?? null);
            unset($content['image_upload']);
        } elseif ($component === 'media_banner') {
            foreach (['desktop' => 'desktop_image', 'mobile' => 'mobile_image', 'fallback' => 'fallback_image'] as $slot => $imageKey) {
                $content[$imageKey] = $request->hasFile("content.{$slot}_upload")
                    ? $store($request->file("content.{$slot}_upload"))
                    : ($content[$imageKey] ?? $old[$imageKey] ?? null);
                unset($content["{$slot}_upload"]);
            }
            $content['video_file'] = $request->hasFile('content.video_upload')
                ? $store($request->file('content.video_upload'))
                : ($content['video_file'] ?? $old['video_file'] ?? null);
            unset($content['video_upload']);
        }

        $content = $this->normalizeBooleans($component, $content);
        foreach (['slides', 'items'] as $collection) {
            if (isset($content[$collection]) && is_array($content[$collection])) {
                $content[$collection] = collect($content[$collection])->sortBy('sort_order')->values()->all();
            }
        }
        return $content;
    }

    private function mergeItemImages(Request $request, array $items, array $oldItems, array $devices, callable $store, string $collection): array
    {
        $oldByKey = collect($oldItems)->keyBy('key');
        foreach ($items as $index => &$item) {
            $item['key'] = $item['key'] ?? ('item-' . $index . '-' . substr(md5((string) microtime(true)), 0, 6));
            $old = $oldByKey->get($item['key'], []);
            foreach ($devices as $device) {
                $prefix = $device ? "{$device}_" : '';
                $uploadKey = "content.{$collection}.{$index}.{$prefix}upload";
                $imageKey = "{$prefix}image";
                $item[$imageKey] = $request->hasFile($uploadKey) ? $store($request->file($uploadKey)) : ($item[$imageKey] ?? $old[$imageKey] ?? null);
                unset($item["{$prefix}upload"]);
            }
        }
        unset($item);
        return array_values($items);
    }

    private function normalizeBooleans(string $component, array $content): array
    {
        foreach (['autoplay', 'show_old_price', 'show_current_price', 'show_percentage', 'show_arrows', 'allow_swipe',
                  'muted', 'loop', 'show_controls', 'grayscale', 'show_count'] as $key) {
            if (array_key_exists($key, $content)) $content[$key] = filter_var($content[$key], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($content['items'])) {
            foreach ($content['items'] as &$item) $item['enabled'] = filter_var($item['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            unset($item);
        }
        if ($component === 'hero' && isset($content['slides'])) {
            foreach ($content['slides'] as &$slide) $slide['enabled'] = filter_var($slide['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            unset($slide);
        }
        return $content;
    }

    private function variantFor(string $component, array $content): string
    {
        return match ($component) {
            'hero' => $content['mode'] ?? 'single',
            'announcements' => 'auto',
            'featured_categories' => $content['display'] ?? 'images',
            'discounts' => $content['layout'] ?? 'grid',
            'media_banner' => $content['media_type'] ?? 'image',
            // Secciones nuevas: la variante visual viaja dentro del contenido.
            'collection_showcase', 'brands', 'testimonials', 'gallery', 'faq', 'wa_advisory', 'cta_banner', 'locations', 'about_preview', 'info_strip'
                => $content['variant'] ?? (StorefrontSections::defaults($this->project())[$component]['variant'] ?? 'default'),
            default => StorefrontSections::defaults($this->project())[$component]['variant'] ?? 'default',
        };
    }

    // Compatibilidad con el formulario anterior.
    public function section(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('store_sections', 'id')->where('project_id', $project->id)],
            'page' => ['required', Rule::in(['home'])],
            'component' => ['required', Rule::in(array_keys(StorefrontSections::COMPONENTS))],
            'variant' => 'nullable|string|max:60',
            'title' => 'nullable|string|max:180', 'body' => 'nullable|string|max:4000', 'button_text' => 'nullable|string|max:80',
            'button_url' => 'nullable|string|max:500', 'image' => 'nullable|image|max:4096', 'sort_order' => 'nullable|integer|min:0',
            'publish_from' => 'nullable|date', 'publish_until' => 'nullable|date|after_or_equal:publish_from',
        ]);
        $old = $request->integer('id') ? $project->storeSections()->findOrFail($request->integer('id')) : null;
        $image = data_get($old?->content, 'image');
        if ($request->hasFile('image')) $image = $request->file('image')->store('store-sections', 'public');
        $this->sectionWrites->saveAndPublish($project, $data['page'], $data['component'], [
            'variant' => $data['variant'] ?? null,
            'content' => ['title' => $data['title'] ?? '', 'body' => $data['body'] ?? '', 'button_text' => $data['button_text'] ?? '', 'button_url' => $data['button_url'] ?? '', 'image' => $image],
            'sort_order' => $data['sort_order'] ?? 0,
            'publish_from' => $data['publish_from'] ?? null,
            'publish_until' => $data['publish_until'] ?? null,
            'is_enabled' => $request->boolean('is_enabled'),
            'show_desktop' => $request->boolean('show_desktop'),
            'show_tablet' => $request->boolean('show_tablet'),
            'show_mobile' => $request->boolean('show_mobile'),
        ]);
        return back()->with('success', 'Sección guardada.');
    }

    public function deleteSection($id)
    {
        $this->project()->storeSections()->findOrFail($id)->delete();
        return back()->with('success', 'Sección eliminada.');
    }

    public function page(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'key' => ['required','string','max:50','regex:/^[a-z0-9-]+$/'], 'title' => 'required|string|max:160', 'body' => 'nullable|string|max:12000',
            'history' => 'nullable|string|max:12000', 'mission' => 'nullable|string|max:3000', 'vision' => 'nullable|string|max:3000',
            'values' => 'nullable|string|max:5000', 'team' => 'nullable|string|max:8000', 'image' => 'nullable|image|max:4096',
            'gallery_images' => ['nullable','array','max:8'], 'gallery_images.*' => ['image','mimes:jpg,jpeg,png,webp','max:4096'],
            'button_heading' => 'nullable|string|max:160', 'button_body' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:80', 'button_url' => ['nullable','string','max:500',function($attribute,$value,$fail){if($value&&!str_starts_with($value,'/')&&!(filter_var($value,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($value,PHP_URL_SCHEME)),['http','https'],true)))$fail('El enlace debe ser interno o comenzar con http:// o https://.');}],
            'phone' => 'nullable|string|max:40', 'whatsapp' => 'nullable|string|max:40', 'email' => 'nullable|email|max:160',
            'address' => 'nullable|string|max:255', 'hours' => 'nullable|string|max:255',
            'map_url' => 'nullable|url:http,https|max:1000', 'confirmation_message' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|url:http,https|max:500', 'instagram_url' => 'nullable|url:http,https|max:500',
            'linkedin_url' => 'nullable|url:http,https|max:500', 'tiktok_url' => 'nullable|url:http,https|max:500',
            // Nosotros institucional (hero + quiénes somos + diferenciales)
            'label' => 'nullable|string|max:60', 'heading' => 'nullable|string|max:160',
            'hero_title' => 'nullable|string|max:160', 'hero_subtitle' => 'nullable|string|max:200', 'hero_desc' => 'nullable|string|max:500',
            'hero_align' => 'nullable|in:left,center', 'hero_height' => 'nullable|in:compact,standard,tall',
            'hero_overlay' => 'nullable|integer|min:0|max:80',
            'hero_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'hero_image_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'mission_icon' => 'nullable|string|max:30', 'vision_icon' => 'nullable|string|max:30',
            'differentials' => 'nullable|string|max:6000',
        ]);

        // Diferenciales: llega como JSON del repetidor; se sanea campo por campo (máx. 6).
        if (array_key_exists('differentials', $data)) {
            $rawDiffs = json_decode((string) $data['differentials'], true);
            $data['differentials'] = collect(is_array($rawDiffs) ? $rawDiffs : [])
                ->filter(fn ($d) => is_array($d) && trim((string) ($d['title'] ?? '')) !== '')
                ->take(6)
                ->map(fn ($d) => [
                    'icon' => mb_substr((string) ($d['icon'] ?? 'star'), 0, 30),
                    'title' => mb_substr(trim((string) $d['title']), 0, 80),
                    'text' => mb_substr(trim((string) ($d['text'] ?? '')), 0, 200),
                    'enabled' => (bool) ($d['enabled'] ?? true),
                ])->values()->all();
        }

        // Imágenes del hero: conservar la previa si no se sube una nueva.
        $existingContent = \App\Models\StorePage::where('project_id', $project->id)
            ->where('key', $data['key'])->value('content') ?? [];
        foreach (['hero_image', 'hero_image_mobile'] as $heroKey) {
            if ($request->hasFile($heroKey)) {
                $data[$heroKey] = $request->file($heroKey)->store('store-pages', 'public');
            } else {
                $data[$heroKey] = $existingContent[$heroKey] ?? null;
            }
        }
        $newImage = $request->hasFile('image')
            ? $request->file('image')->store('store-pages', 'public')
            : null;
        $newGallery = [];
        foreach ($request->file('gallery_images', []) as $file) {
            $newGallery[] = $file->store('store-pages/gallery', 'public');
        }
        unset($data['image'], $data['gallery_images']);
        foreach (['show_history','show_mission','show_vision','show_values','show_team','show_gallery','show_cta','require_phone','require_email','hero_enabled','differentials_enabled'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        $data['is_enabled'] = $request->boolean('is_enabled');

        $this->pageWrites->save($project, $data, $newImage, $newGallery);

        return back()->with('success', 'Página guardada.');
    }

    public function popup(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'title' => 'nullable|string|max:160', 'description' => 'nullable|string|max:4000', 'image' => 'nullable|image|max:4096',
            'button_text' => 'nullable|string|max:80', 'button_url' => 'nullable|string|max:500', 'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at', 'delay_seconds' => 'nullable|integer|min:0|max:60',
            'frequency' => 'required|in:session,day,always',
            'trigger' => 'nullable|in:delay,exit', 'position' => 'nullable|in:center,bottom_right',
        ]);
        $newImage = $request->hasFile('image')
            ? $request->file('image')->store('store-popups', 'public')
            : null;
        unset($data['image']);
        $data += [
            'is_enabled' => $request->boolean('is_enabled'),
            'show_desktop' => $request->boolean('show_desktop'),
            'show_mobile' => $request->boolean('show_mobile'),
        ];

        $this->popupWrites->save($project, $data, $newImage);

        return back()->with('success', 'Pop-up guardado.');
    }

    public function complaintStatus(Request $request, $id)
    {
        $complaint = $this->project()->hasMany(\App\Models\Complaint::class)->findOrFail($id);
        $complaint->update($request->validate(['status' => 'required|in:received,in_review,resolved,closed']));
        return back()->with('success', 'Estado del reclamo actualizado.');
    }
}
