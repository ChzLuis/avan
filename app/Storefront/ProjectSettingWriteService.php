<?php

namespace App\Storefront;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Fase 1C — Escritura canónica y aislada de project_settings desde el Diseñador.
 *
 * Garantías:
 *  - Escrituras transaccionales, aisladas por proyecto.
 *  - Whitelist de claves por pestaña: una pestaña no toca claves de otra.
 *  - false / 0 / "0" / "" se conservan como valores explícitos.
 *  - Campos ausentes en el request NO se sobrescriben.
 *  - Se escribe la clave canónica; los aliases heredados NO se escriben (siguen
 *    siendo legibles vía StorefrontContextBuilder::ALIASES).
 */
final class ProjectSettingWriteService
{
    /**
     * Claves de texto/valor permitidas por pestaña del Diseñador.
     * Refleja el contrato del formulario (`_design_tab`).
     */
    public const TAB_KEYS = [
        'marca' => [
            'primary_color', 'secondary_color', 'whatsapp_msg',
            'logo_url', 'logo_height', 'favicon_url',
            'font_title', 'font_body', 'border_radius', 'currency_symbol',
            'header_bg_color', 'header_text_color', 'header_height',
            'footer_bg_color', 'footer_text_color', 'footer_logo_height',
            'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'twitter_url', 'linkedin_url',
        ],
        'portada' => [
            'hero_title', 'hero_subtitle', 'hero_badge', 'hero_bg_color',
            'hero_image', 'hero_overlay', 'hero_align', 'hero_height',
            'hero_cta1_show', 'hero_cta1_text', 'hero_cta2_show', 'hero_cta2_text',
            'banner1_title', 'banner1_sub', 'banner2_title', 'banner2_sub',
            'announcement_text', 'announcement_bg',
            'countdown_label', 'countdown_end',
            'split_left_title', 'split_left_sub', 'split_right_title', 'split_right_sub',
            'trust_icon_1', 'trust_text_1', 'trust_icon_2', 'trust_text_2', 'trust_icon_3', 'trust_text_3',
            'trust_icon_4', 'trust_text_4',
            'tab1_label', 'tab2_label', 'tab3_label',
        ],
        'catalogo' => [
            'catalog_section_title', 'card_style', 'catalog_cols_desktop', 'catalog_cols_mobile',
            'catalog_filter_price', 'catalog_filter_cats', 'catalog_filter_sale', 'catalog_filter_search',
            'catalog_badge_sale', 'catalog_badge_new', 'catalog_badge_featured', 'catalog_badge_sold_out',
            'catalog_show_ratings', 'catalog_quick_view', 'catalog_show_sku', 'catalog_show_stock', 'wholesale_enabled',
            'btn_cart_text', 'btn_quote_text', 'btn_shape', 'btn_show_icon',
            'float_cart_show', 'float_cart_pos', 'float_wa_show', 'float_wa_tooltip', 'float_wa_pos',
        ],
        'sistema' => [
            'store_mode', 'quote_price_display', 'quote_whatsapp', 'quote_whatsapp_country', 'quote_wa_msg',
            'product_button_mode', 'btn_inquiry_text',
            'featured_categories_band_bg', 'featured_categories_band_text', 'flash_sale_style', 'flash_sale_accent',
            'shipping_enabled', 'shipping_cost', 'shipping_free_from', 'require_address',
            'show_flash_sale', 'show_testimonials', 'show_newsletter', 'show_trust_strip',
            'payment_yape_number', 'payment_yape_name', 'payment_yape_qr', 'payment_plin_number', 'payment_bank_details', 'payment_manual_instructions',
            'payment_bank_bcp', 'payment_bank_interbank', 'payment_bank_bbva', 'payment_bank_nacion', 'payment_bank_scotiabank',
            'culqi_public_key', 'culqi_mode',
            'footer_tagline', 'footer_copyright', 'footer_dev_text',
            'contact_email', 'contact_phone', 'business_hours',
            'footer_benefit_1_icon', 'footer_benefit_1_text',
            'footer_benefit_2_icon', 'footer_benefit_2_text',
            'footer_benefit_3_icon', 'footer_benefit_3_text',
            'footer_pages', 'footer_store_pages',
            'footer_newsletter_title', 'footer_newsletter_url',
            'footer_show_social', 'footer_show_categories', 'footer_show_newsletter',
            'footer_show_benefits', 'footer_show_address',
            'txt_no_results', 'txt_search_placeholder', 'txt_view_more', 'txt_all_cats',
            'login_bg_type', 'login_color1', 'login_color2', 'login_bg_image', 'login_heading', 'login_subtitle',
            'seo_title', 'seo_description', 'seo_keywords',
        ],
        'checkout' => [
            'cart_title', 'cart_empty_msg', 'btn_checkout_text', 'btn_send_quote_text',
            'checkout_fields',
        ],
    ];

    /**
     * Booleanos que deben persistir su estado "apagado" (0) por pestaña, incluso
     * cuando el checkbox no viene en el request.
     */
    public const BOOLEAN_KEYS = [
        'portada' => ['hero_cta1_show', 'hero_cta2_show', 'age_gate'],
        'catalogo' => ['catalog_filter_price', 'catalog_filter_cats', 'catalog_filter_sale', 'catalog_filter_search', 'catalog_show_ratings', 'catalog_quick_view', 'catalog_show_sku', 'catalog_show_stock', 'wholesale_enabled', 'btn_show_icon', 'float_cart_show', 'float_wa_show'],
        'sistema' => ['shipping_enabled', 'require_address', 'show_flash_sale', 'show_testimonials', 'show_newsletter', 'show_trust_strip', 'footer_show_social', 'footer_show_categories', 'footer_show_newsletter', 'footer_show_benefits', 'footer_show_address', 'payment_manual_enabled', 'culqi_enabled', 'mp_enabled'],
    ];

    /**
     * Mapa alias-heredado -> clave canónica. Cuando el formulario envía la clave
     * heredada, se persiste bajo la canónica (el alias sigue siendo legible).
     */
    public const CANONICALIZE = [
        'logo_url' => 'header_logo_url',
        'logo_height' => 'header_logo_height',
        'whatsapp_msg' => 'quote_wa_msg',
    ];

    /**
     * @param array<string,mixed> $input   Valores enviados (solo los presentes).
     * @param array<int,string>   $present Claves realmente presentes en el request.
     */
    public function saveDesignTab(Project $project, string $tab, array $input, array $present): int
    {
        $allowed = self::TAB_KEYS[$tab] ?? [];
        if ($allowed === []) {
            throw new \InvalidArgumentException("Pestaña de diseño no válida: {$tab}");
        }

        return DB::transaction(function () use ($project, $tab, $input, $present, $allowed) {
            $written = 0;
            $presentSet = array_flip($present);

            // 1. Valores de texto/valor: solo claves de esta pestaña y presentes en el request.
            foreach ($allowed as $key) {
                if (!array_key_exists($key, $presentSet)) {
                    continue; // ausente -> no sobrescribe
                }
                $value = $input[$key] ?? '';
                $canonical = self::CANONICALIZE[$key] ?? $key;
                $project->settings()->updateOrCreate(['key' => $canonical], ['value' => $value]);
                $written++;
            }

            // 2. Booleanos de esta pestaña: persisten "0" aunque el checkbox no venga.
            foreach (self::BOOLEAN_KEYS[$tab] ?? [] as $boolKey) {
                $canonical = self::CANONICALIZE[$boolKey] ?? $boolKey;
                $on = (($input[$boolKey] ?? null) === '1');
                $project->settings()->updateOrCreate(['key' => $canonical], ['value' => $on ? '1' : '0']);
                $written++;
            }

            return $written;
        });
    }

    /**
     * Persiste arrays de checkboxes (JSON) para la pestaña sistema.
     *
     * @param array<string,array> $arrays  key => array de valores
     */
    public function saveJsonArrays(Project $project, array $arrays): void
    {
        if ($arrays === []) {
            return;
        }
        DB::transaction(function () use ($project, $arrays) {
            foreach ($arrays as $key => $values) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => json_encode(array_values($values))]);
            }
        });
    }

    /**
     * Persiste llaves secretas solo si el valor no es la máscara (••).
     *
     * @param array<string,?string> $secrets key => valor enviado
     */
    public function saveSecrets(Project $project, array $secrets): void
    {
        DB::transaction(function () use ($project, $secrets) {
            foreach ($secrets as $key => $val) {
                if ($val && !str_starts_with($val, '••')) {
                    $project->settings()->updateOrCreate(['key' => $key], ['value' => $val]);
                }
            }
        });
    }
}
