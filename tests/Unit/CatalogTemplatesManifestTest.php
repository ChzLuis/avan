<?php

namespace Tests\Unit;

use App\Support\CatalogTemplates;
use PHPUnit\Framework\TestCase;

class CatalogTemplatesManifestTest extends TestCase
{
    public function test_supported_catalog_contains_exactly_the_two_official_engines(): void
    {
        $this->assertSame(['ecommerce', 'direct'], CatalogTemplates::supportedKeys());
        $this->assertSame(CatalogTemplates::supportedKeys(), array_keys(CatalogTemplates::supported()));
    }

    public function test_supported_templates_expose_complete_application_metadata(): void
    {
        $expected = [
            'ecommerce' => ['Ecommerce', 'Sitio web completo con tienda online', 'public.templates.ecommerce'],
            'direct' => ['Catálogo Directo', 'Catálogo simple para ventas y cotizaciones', 'public.templates.direct'],
        ];

        foreach ($expected as $key => [$name, $description, $view]) {
            $theme = CatalogTemplates::supportedTheme($key);

            $this->assertSame($key, $theme['key']);
            $this->assertSame($name, $theme['name']);
            $this->assertSame($description, $theme['description']);
            $this->assertSame($view, $theme['view']);
            $this->assertTrue($theme['supported']);
            $this->assertNotEmpty($theme['capabilities']);
        }
    }

    public function test_legacy_templates_remain_recognized_but_are_not_supported_for_selection(): void
    {
        $this->assertNotNull(CatalogTemplates::get('computienda'));
        $this->assertFalse(CatalogTemplates::isSupported('computienda'));
        $this->assertNull(CatalogTemplates::supportedTheme('computienda'));
        $this->assertNotNull(CatalogTemplates::get('ella'));
        $this->assertFalse(CatalogTemplates::isSupported('ella'));
        $this->assertNull(CatalogTemplates::supportedTheme('ella'));
    }

    public function test_technology_template_exposes_a_component_manifest(): void
    {
        $manifest = CatalogTemplates::manifest('tecnologia');

        $this->assertSame('tecnologia', $manifest['template']);
        $this->assertNotEmpty($manifest['components']);
        $this->assertArrayHasKey('hero', $manifest['component_catalog']);
    }
}
