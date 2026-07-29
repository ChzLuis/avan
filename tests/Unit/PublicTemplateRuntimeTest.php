<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PublicTemplateRuntimeTest extends TestCase
{
    public function test_every_public_template_uses_the_shared_store_runtime(): void
    {
        $files = glob(dirname(__DIR__, 2).'/resources/views/public/templates/*.blade.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            if (!str_contains(file_get_contents($file), '</body>')) continue;
            $this->assertStringContainsString('public-store-runtime', file_get_contents($file), basename($file));
        }

        $catalog = file_get_contents(dirname(__DIR__, 2).'/resources/views/public/catalog.blade.php');
        $this->assertStringContainsString('public-store-runtime', $catalog);
    }

    public function test_shared_runtime_consumes_global_design_settings(): void
    {
        $runtime = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/public-store-runtime.blade.php');
        foreach ([
            'primary_color','secondary_color','font_title','font_body','logo_url','logo_height','favicon_url',
            'header_bg_color','header_text_color','header_height','footer_bg_color','footer_text_color','footer_logo_height',
            'border_radius','currency_symbol','facebook_url','instagram_url','tiktok_url','youtube_url','twitter_url','linkedin_url',
            'hero_title','hero_subtitle','hero_badge','hero_bg_color','hero_image','hero_overlay','hero_align','hero_height',
            'hero_cta1_show','hero_cta1_text','hero_cta2_show','hero_cta2_text','announcement_text','announcement_bg',
            'banner1_title','banner2_title','countdown_label','countdown_end',
            'split_left_title','split_right_title','age_gate',
            'catalog_section_title','card_style','catalog_cols_desktop','catalog_cols_mobile','catalog_filter_price',
            'catalog_filter_cats','catalog_filter_sale','catalog_filter_search','catalog_badge_sale','catalog_badge_new',
            'catalog_badge_featured','catalog_badge_sold_out','catalog_show_ratings','catalog_quick_view','catalog_show_sku',
            'catalog_show_stock','wholesale_enabled','btn_cart_text','btn_quote_text','btn_shape','btn_show_icon',
            'float_cart_show','float_cart_pos','float_wa_show','float_wa_tooltip','float_wa_pos',
            'store_mode','quote_price_display','quote_whatsapp','quote_wa_msg','shipping_enabled','require_address',
            'footer_tagline','footer_copyright','footer_dev_text','contact_email','contact_phone','business_hours',
            'footer_show_social','footer_show_categories','footer_show_newsletter','footer_show_benefits','footer_show_address',
            'cart_title','cart_empty_msg','btn_checkout_text','btn_send_quote_text','txt_no_results','txt_search_placeholder',
            'txt_view_more','txt_all_cats','checkout_fields','seo_title','seo_description','seo_keywords',
        ] as $key) {
            $this->assertStringContainsString($key, $runtime, $key);
        }

        foreach (['banner{$i}_sub','split_{$side}_sub','trust_icon_{$i}','trust_text_{$i}','tab{$i}_label'] as $dynamicKey) {
            $this->assertStringContainsString($dynamicKey, $runtime, $dynamicKey);
        }
    }

    public function test_builder_hero_replaces_the_complete_native_hero_section(): void
    {
        $runtime = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/public-store-runtime.blade.php');

        $this->assertStringContainsString(
            "const nativeHero = heroTitle?.closest('section') || heroTitle?.closest('[class*=\"hero\" i],[class*=\"banner\" i]');",
            $runtime
        );
        $this->assertStringContainsString('nativeHero.hidden = true', $runtime);
    }

    public function test_every_managed_home_section_can_replace_complete_native_sections(): void
    {
        $runtime = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/public-store-runtime.blade.php');

        $this->assertStringContainsString("document.querySelectorAll('[data-store-native-section]')", $runtime);
        $this->assertStringContainsString('owners.some(component => managedComponents.has(component))', $runtime);
        $this->assertStringContainsString('nativeSection.hidden = true', $runtime);

        foreach (['announcements', 'daily_offer', 'benefits', 'featured_categories'] as $component) {
            $this->assertStringContainsString('data-store-native-section="'.$component.'"', $runtime, $component);
        }
    }

    public function test_native_section_markers_only_use_canonical_builder_components(): void
    {
        $allowed = ['hero', 'benefits', 'announcements', 'featured_categories', 'daily_offer', 'discounts', 'featured_products', 'blog'];
        $files = array_merge(
            [
                dirname(__DIR__, 2).'/resources/views/components/public-store-runtime.blade.php',
                dirname(__DIR__, 2).'/resources/views/public/catalog.blade.php',
            ],
            glob(dirname(__DIR__, 2).'/resources/views/public/templates/*.blade.php') ?: []
        );

        $found = [];

        foreach ($files as $file) {
            preg_match_all('/data-store-native-section="([^"]+)"/', file_get_contents($file), $matches);
            foreach ($matches[1] as $components) {
                foreach (preg_split('/\s+/', trim($components)) as $component) {
                    $this->assertContains($component, $allowed, basename($file).': '.$component);
                    $found[$component] = true;
                }
            }
        }

        foreach (array_diff($allowed, ['blog']) as $component) {
            $this->assertArrayHasKey($component, $found, $component);
        }
    }
}
