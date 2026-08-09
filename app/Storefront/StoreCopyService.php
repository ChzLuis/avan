<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Copiar configuración de otra tienda (B7).
 *
 * - Solo tiendas del MISMO dueño (o superadmin).
 * - Todo se copia a BORRADOR (builder_drafts + draft_* de secciones):
 *   nada toca la tienda pública hasta Publicar.
 * - Nunca copia: productos, dominios, credenciales, claves de facturación.
 */
class StoreCopyService
{
    /** Claves por grupo copiable (curadas del contrato del diseñador). */
    public const GROUPS = [
        'apariencia' => [
            'catalog_template', 'primary_color', 'secondary_color', 'font', 'btn_shape',
            'header_bg_color', 'header_text_color', 'header_sticky', 'header_logo_height',
            'announcement_text', 'announcement_bg', 'announcement_color', 'announcement_font_size',
            'announcement_align', 'announcement_full_width', 'announcement_show',
            'footer_copyright', 'favicon_url',
        ],
        'inicio' => [
            'hero_title', 'hero_subtitle', 'hero_image', 'hero_image_2', 'hero_image_3',
            'hero_mobile_image_1', 'hero_mobile_image_2', 'hero_mobile_image_3',
            'trust_section_title', 'trust_section_style', 'trust_mobile_carousel',
            'trust_icon_1', 'trust_text_1', 'trust_icon_2', 'trust_text_2',
            'trust_icon_3', 'trust_text_3', 'trust_icon_4', 'trust_text_4',
            'promo_style', 'promo_autoplay', 'promo_title_1', 'promo_subtitle_1',
            'promo_title_2', 'promo_subtitle_2', 'promo_title_3', 'promo_subtitle_3',
            'featured_categories_title', 'featured_categories_style',
            'featured_categories_band_bg', 'featured_categories_band_text',
            'flash_sale_style', 'flash_sale_accent', 'featured_products_view',
        ],
        'venta' => [
            'store_mode', 'product_button_mode', 'btn_inquiry_text', 'btn_cart_text',
            'payment_manual_enabled', 'payment_yape_number', 'payment_yape_name',
            'payment_plin_number', 'payment_bank_details',
            'shipping_enabled', 'shipping_cost', 'shipping_free_from', 'require_address',
        ],
    ];

    /** Tiendas desde las que este usuario puede copiar. */
    public function sourcesFor(User $user, Project $current): array
    {
        $q = Project::query()->where('id', '!=', $current->id)->where('is_active', true);
        if (!$user->is_superadmin) $q->where('owner_id', $user->id);

        return $q->orderBy('name')->get(['id', 'name', 'slug'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'slug' => $p->slug])->all();
    }

    /** Resumen previo: qué se copiaría (sin escribir nada). */
    public function summary(Project $source, array $parts): array
    {
        $keys = $this->keysFor($parts);
        $values = $source->settings()->whereIn('key', $keys)->pluck('value', 'key');
        $sections = in_array('inicio', $parts, true)
            ? $source->storeSections()->where('page', 'home')->count() : 0;

        return [
            'settings' => $values->count(),
            'sections' => $sections,
            'parts' => array_values($parts),
        ];
    }

    /** Ejecuta la copia (transaccional, a borrador). Devuelve conteos. */
    public function copy(User $user, Project $target, Project $source, array $parts, BuilderDraftService $drafts): array
    {
        abort_unless($user->is_superadmin || $source->owner_id === $user->id, 403);
        abort_if($source->id === $target->id, 422);

        $keys = $this->keysFor($parts);

        return DB::transaction(function () use ($user, $target, $source, $parts, $keys, $drafts) {
            $copiedSettings = 0;
            foreach ($source->settings()->whereIn('key', $keys)->pluck('value', 'key') as $key => $value) {
                $drafts->putSetting($target, $key, $value, $user->id);
                $copiedSettings++;
            }

            $copiedSections = 0;
            if (in_array('inicio', $parts, true)) {
                $writer = app(StoreSectionWriteService::class);
                \App\Support\StorefrontSections::ensure($target);
                foreach ($source->storeSections()->where('page', 'home')->get() as $section) {
                    $writer->saveDraft($target, 'home', $section->component, [
                        'content' => is_array($section->content) ? $section->content : [],
                        'is_enabled' => (bool) $section->is_enabled,
                        'sort_order' => (int) $section->sort_order,
                        'show_desktop' => (bool) ($section->show_desktop ?? true),
                        'show_mobile' => (bool) ($section->show_mobile ?? true),
                        'show_tablet' => (bool) ($section->show_tablet ?? true),
                    ]);
                    $copiedSections++;
                }
            }

            Log::info('builder', ['event' => 'builder_store_copied', 'project' => $target->id,
                'source' => $source->id, 'parts' => $parts, 'settings' => $copiedSettings, 'sections' => $copiedSections]);

            return ['settings' => $copiedSettings, 'sections' => $copiedSections];
        });
    }

    private function keysFor(array $parts): array
    {
        $keys = [];
        foreach ($parts as $part) $keys = array_merge($keys, self::GROUPS[$part] ?? []);

        return array_values(array_unique($keys));
    }
}
