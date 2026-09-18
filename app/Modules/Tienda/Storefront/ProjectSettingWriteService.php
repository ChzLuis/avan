<?php

namespace App\Modules\Tienda\Storefront;

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
            'catalog_watermark', 'catalog_watermark_opacity',
            'featured_categories_promo_card', 'featured_categories_promo_title', 'featured_categories_promo_text', 'featured_categories_promo_cta', 'featured_categories_promo_image',
            'hero_title_highlight', 'hero_panel_title', 'hero_panel_item_1', 'hero_panel_item_2', 'hero_panel_item_3', 'hero_panel_item_4',
            'hero_panel_icon_1', 'hero_panel_icon_2', 'hero_panel_icon_3', 'hero_panel_icon_4', 'featured_categories_tiles_media',
            'promo_cards_side_card', 'promo_cards_side_title', 'promo_cards_side_text', 'promo_cards_side_cta', 'promo_cards_side_url', 'promo_cards_side_image',
            'promo_cards_side_item_1', 'promo_cards_side_item_2', 'promo_cards_side_item_3', 'promo_cards_side_item_4',
            'promo_cards_side_icon_1', 'promo_cards_side_icon_2', 'promo_cards_side_icon_3', 'promo_cards_side_icon_4',
            'brands_page_title', 'brands_page_subtitle', 'promo_cards_title', 'promo_cards_subtitle', 'promo_cards_limit', 'catalog_pdf_title', 'catalog_pdf_subtitle',
            'catalog_pdf_banner', 'catalog_pdf_banner_image', 'catalog_pdf_intro', 'catalog_pdf_claim', 'catalog_pdf_feature_1', 'catalog_pdf_feature_2', 'catalog_pdf_feature_3',
            'catalog_pdf_cover_title', 'catalog_pdf_cover_year', 'catalog_pdf_cover_tagline', 'catalog_pdf_cover_note', 'catalog_pdf_cover_image',
            'catalog_pdf_options_title', 'catalog_pdf_options_text', 'catalog_pdf_button', 'catalog_pdf_info_title',
            'catalog_pdf_info_1', 'catalog_pdf_info_2', 'catalog_pdf_info_3', 'catalog_pdf_info_4', 'catalog_pdf_info_5', 'catalog_pdf_info_6', 'catalog_pdf_quote',
            'mega_btn_bg_color', 'mega_btn_text_color',
            'mega_enabled', 'mega_button_text', 'mega_all_text', 'mega_max_cats', 'mega_brands', 'mega_brands_title', 'mega_brands_max', 'mega_quick_title',
            'mega_quick_1_label', 'mega_quick_1_url', 'mega_quick_1_icon', 'mega_quick_2_label', 'mega_quick_2_url', 'mega_quick_2_icon',
            'mega_quick_3_label', 'mega_quick_3_url', 'mega_quick_3_icon', 'mega_quick_4_label', 'mega_quick_4_url', 'mega_quick_4_icon',
            'mega_help_title', 'mega_help_text', 'mega_help_button', 'mega_help_url',
            'pdp_layout', 'pdp_banner', 'pdp_avail_text', 'pdp_quote_now_text', 'pdp_volume_title', 'pdp_volume_text', 'pdp_volume_cta',
            'pdp_ficha_text', 'pdp_tab_instalacion', 'pdp_tab_instalacion_label', 'pdp_tab_envios', 'pdp_tab_envios_label', 'pdp_tab_garantia', 'pdp_tab_garantia_label', 'pdp_trust_1_title', 'pdp_trust_1_text', 'pdp_trust_2_title', 'pdp_trust_2_text', 'pdp_trust_3_title', 'pdp_trust_3_text',
            'btn_cart_text', 'btn_quote_text', 'btn_shape', 'btn_show_icon',
            'float_cart_show', 'float_cart_pos', 'float_wa_show', 'float_wa_tooltip', 'float_wa_pos',
        ],
        'sistema' => [
            'store_mode', 'quote_price_display', 'quote_whatsapp_country', 'quote_wa_msg', // quote_whatsapp: solo Constructor
            'product_button_mode', 'btn_inquiry_text',
            'featured_categories_band_bg', 'featured_categories_band_text', 'flash_sale_style', 'flash_sale_accent',
            'shipping_enabled', 'shipping_cost', 'shipping_free_from', 'require_address',
            'show_flash_sale', 'show_testimonials', 'show_newsletter', 'show_trust_strip',
            'payment_yape_number', 'payment_yape_name', 'payment_yape_qr', 'payment_plin_number', 'payment_bank_details', 'payment_manual_instructions',
            'payment_bank_bcp', 'payment_bank_interbank', 'payment_bank_bbva', 'payment_bank_nacion', 'payment_bank_scotiabank',
            'culqi_public_key', 'culqi_mode',
            'footer_tagline', 'footer_copyright', 'footer_dev_text',
            // contact_email / contact_phone / business_hours salieron de esta
            // whitelist: se editan solo en el Constructor (revision 01).
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
