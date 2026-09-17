<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO de la tienda: lo que Google necesita para entender el negocio (2026-09-07).
 *
 * La portada declaraba `Product` solo en las fichas y NADA en el resto: Google
 * veía un sitio cualquiera, sin saber que detrás hay una empresa con RUC,
 * dirección y teléfono. Además salían dos H1 compitiendo, porque el hero de
 * respaldo (oculto) también usaba H1.
 */
class SeoTiendaTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Distribuidora Muruhuay', 'slug' => 'seo-'.uniqid(), 'is_active' => true,
        ]);
        $base = [
            'storefront_structure_v2' => '1', 'catalog_template' => 'computienda',
            'ruc' => '20614995590', 'razon_social' => 'DISTRIBUIDORA MURUHUAY S.A.C.',
            'contact_address' => 'Av Guillermo Dansey 444', 'contact_city' => 'Lima',
            'contact_phone' => '987654321', 'business_hours' => 'Lun-Vie 9-18',
            'facebook_url' => 'https://facebook.com/muruhuay',
        ];
        foreach ($ajustes + $base as $k => $v) {
            $p->settings()->create(['key' => $k, 'value' => (string) $v]);
        }

        return $p;
    }

    private function schema(string $html): array
    {
        $this->assertTrue((bool) preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m),
            'la pagina no declara ningun dato estructurado');

        return array_map(fn ($j) => json_decode($j, true), $m[1]);
    }

    /** La portada le dice a Google que hay un negocio real detrás. */
    public function test_la_portada_declara_el_negocio_local(): void
    {
        $html = $this->get(route('public.catalog', $this->tienda()->slug))->assertOk()->getContent();
        $bloques = $this->schema($html);

        $negocio = collect($bloques)->first(fn ($b) => ($b['@type'] ?? '') === 'LocalBusiness');
        $this->assertNotNull($negocio, 'falta el bloque LocalBusiness');

        $this->assertSame('Distribuidora Muruhuay', $negocio['name']);
        $this->assertSame('20614995590', $negocio['taxID'], 'el RUC identifica a la empresa');
        $this->assertSame('DISTRIBUIDORA MURUHUAY S.A.C.', $negocio['legalName']);
        $this->assertSame('Av Guillermo Dansey 444', $negocio['address']['streetAddress']);
        $this->assertSame('Lima', $negocio['address']['addressLocality']);
        $this->assertSame('PE', $negocio['address']['addressCountry']);
        $this->assertSame('987654321', $negocio['telephone']);
        $this->assertContains('https://facebook.com/muruhuay', $negocio['sameAs']);
    }

    /** Una sola página, un solo H1: es lo que Google espera. */
    public function test_la_portada_tiene_un_unico_h1(): void
    {
        $html = $this->get(route('public.catalog', $this->tienda()->slug))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'),
            'hay mas de un H1 compitiendo por el tema de la pagina');
    }

    /** Un negocio sin dirección no publica un bloque con huecos vacíos. */
    public function test_sin_datos_no_se_inventan_campos(): void
    {
        $p = $this->tienda(['contact_address' => '', 'contact_city' => '', 'ruc' => '', 'facebook_url' => '']);
        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        $negocio = collect($this->schema($html))->first(fn ($b) => ($b['@type'] ?? '') === 'LocalBusiness');
        $this->assertNotNull($negocio);
        $this->assertArrayNotHasKey('address', $negocio);
        $this->assertArrayNotHasKey('taxID', $negocio);
        $this->assertArrayNotHasKey('sameAs', $negocio);
    }

    /**
     * Un titulo bueno para Google no debe ensuciar el nombre de la tienda.
     *
     * `seo_title` alimentaba ADEMAS el nombre que sale en cabecera, pie y
     * og:site_name: escribir "Marca | Que vende | Ciudad" dejaba ese texto
     * largo pegado por toda la tienda.
     */
    public function test_un_titulo_largo_no_se_vuelve_el_nombre_de_la_tienda(): void
    {
        $p = $this->tienda(['seo_title' => 'Distribuidora Muruhuay | Pastorales Solares en Lima']);
        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        // La pestaña lleva el titulo completo, tal cual se escribio.
        $this->assertStringContainsString('Distribuidora Muruhuay | Pastorales Solares en Lima</title>', $html);
        // Pero el nombre del sitio sigue siendo el nombre del negocio.
        $this->assertStringContainsString('<meta property="og:site_name" content="Distribuidora Muruhuay">', $html);
    }

    /** Un `seo_title` corto sí puede seguir haciendo de nombre, como antes. */
    public function test_un_titulo_corto_sigue_sirviendo_de_nombre(): void
    {
        $p = $this->tienda(['seo_title' => 'Muruhuay Solar']);
        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:site_name" content="Muruhuay Solar">', $html);
    }

    /** El tipo de negocio se elige desde el panel (SEO → datos estructurados). */
    public function test_el_tipo_de_negocio_es_configurable(): void
    {
        $p = $this->tienda(['schema_type' => 'HardwareStore']);
        $html = $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();

        $this->assertNotNull(
            collect($this->schema($html))->first(fn ($b) => ($b['@type'] ?? '') === 'HardwareStore'),
            'el tipo elegido en el panel debe mandar'
        );
    }
}
