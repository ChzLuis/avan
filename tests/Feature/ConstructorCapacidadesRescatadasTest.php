<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase B2 — capacidades que vivían solo en superficies legacy y ahora tienen
 * su editor oficial en el Constructor, más el emisor único de analítica.
 *
 * Lo que se protege:
 *  · que un ajuste con datos reales no vuelva a quedarse sin editor;
 *  · que la analítica se EMITA de verdad (antes se guardaba y no se pintaba);
 *  · que un identificador inválido no se cuele en el <head> de la tienda;
 *  · que la pantalla de Pagos no vuelva a escribir el WhatsApp maestro.
 */
class ConstructorCapacidadesRescatadasTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda B2', 'slug' => 'b2-'.uniqid(), 'is_active' => true,
            'whatsapp' => '999111222',
        ]);
        foreach ($ajustes as $k => $v) {
            $p->settings()->create(['key' => $k, 'value' => $v]);
        }
        StorefrontSections::ensure($p);

        return $p;
    }

    private function etapa(string $archivo): string
    {
        return file_get_contents(resource_path('views/settings/builder/'.$archivo));
    }

    // ═══ Editores rescatados ════════════════════════════════════════════════

    /** Badges: vivían solo en el Designer y 5 tiendas los tenían configurados. */
    public function test_los_badges_tienen_editor_en_catalogo(): void
    {
        $html = $this->etapa('stages/catalog.blade.php');

        foreach (['catalog_badge_new', 'catalog_badge_sale', 'catalog_badge_featured', 'catalog_badge_sold_out'] as $clave) {
            $this->assertStringContainsString($clave, $html, "Sin editor para {$clave}.");
        }
    }

    /** Pasarelas: vivían solo en settings/payments; 4 tiendas usan cada una. */
    public function test_las_pasarelas_tienen_editor_en_venta(): void
    {
        $html = $this->etapa('stages/sales.blade.php');

        foreach (['culqi_enabled', 'culqi_public_key', 'culqi_mode', 'mp_enabled', 'payment_manual_instructions'] as $clave) {
            $this->assertStringContainsString($clave, $html, "Sin editor para {$clave}.");
        }
    }

    /** Analítica: vivía solo en settings/seo y encima no se emitía. */
    public function test_la_analitica_tiene_editor_en_configuracion(): void
    {
        $html = $this->etapa('advanced.blade.php');

        foreach (['ga_id', 'gtm_id', 'fb_pixel_id', 'tiktok_pixel_id',
                  'google_site_verification', 'bing_site_verification'] as $clave) {
            $this->assertStringContainsString($clave, $html, "Sin editor para {$clave}.");
        }
    }

    /** El WhatsApp NO se pide en Venta: su fuente es 01. */
    public function test_venta_no_pide_el_whatsapp_maestro(): void
    {
        $html = $this->etapa('stages/sales.blade.php');

        $this->assertStringNotContainsString("setSetting('quote_whatsapp'", $html,
            'El WhatsApp se configura en 01 Datos del negocio, no en Venta.');
    }

    // ═══ Emisión real de analítica ══════════════════════════════════════════

    /** Con IDs válidos, las etiquetas llegan al HTML de la tienda. */
    public function test_la_analitica_se_emite_de_verdad(): void
    {
        $p = $this->tienda([
            'catalog_template' => 'computienda',
            'ga_id' => 'G-ABCD123456',
            'gtm_id' => 'GTM-ABC1234',
            'fb_pixel_id' => '123456789012345',
            'google_site_verification' => 'abcdefghijklmnopqrstuvwxyz123456',
        ]);

        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        $this->assertStringContainsString('G-ABCD123456', $html, 'Google Analytics no se emite.');
        $this->assertStringContainsString('googletagmanager.com/gtm.js', $html, 'GTM no se emite.');
        $this->assertStringContainsString("fbq('init','123456789012345')", $html, 'Meta Pixel no se emite.');
        $this->assertStringContainsString('name="google-site-verification"', $html);
    }

    /** Sin IDs configurados no se emite ni una etiqueta: cero peso extra. */
    public function test_sin_ids_no_se_emite_nada(): void
    {
        $p = $this->tienda(['catalog_template' => 'computienda']);

        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
        $this->assertStringNotContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString('analytics.tiktok.com', $html);
    }

    /** Un identificador con formato inválido NUNCA entra en el <head>. */
    public function test_un_identificador_invalido_no_se_emite(): void
    {
        $p = $this->tienda([
            'catalog_template' => 'computienda',
            'ga_id' => "'); alert(1); //",
            'fb_pixel_id' => '<script>alert(1)</script>',
            'gtm_id' => 'no-es-un-gtm',
        ]);

        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('alert(1)', $html, 'Se coló una inyección.');
        $this->assertStringNotContainsString('googletagmanager.com/gtm.js', $html);
        $this->assertStringNotContainsString("fbq('init'", $html);
    }

    // ═══ Fuente canónica ════════════════════════════════════════════════════

    /** La pantalla de Pagos ya no puede escribir el WhatsApp maestro. */
    public function test_pagos_no_escribe_el_whatsapp(): void
    {
        $controlador = file_get_contents(app_path('Http/Controllers/SettingsController.php'));
        $inicio = strpos($controlador, 'function updatePayments');
        $trozo = substr($controlador, $inicio, 900);

        $this->assertStringNotContainsString("'quote_whatsapp'", $trozo,
            'settings/payments volvió a aceptar el WhatsApp: es dato maestro de 01.');
    }
}
