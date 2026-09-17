<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\StorefrontNavigation;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Datos maestros del negocio (teléfono, WhatsApp, dirección): lo que el
 * comerciante escribe en el Constructor debe verse en TODAS las superficies.
 *
 * Había tres puntos que leían solo las columnas de `projects` e ignoraban el
 * ajuste: el encabezado y el pie de la estructura V2, y el schema JSON-LD de la
 * plantilla `ecommerce` — que entregaba a Google datos obsoletos.
 *
 * Cadena de resolución vigente durante la transición: manda el ajuste, la
 * columna es respaldo. Cuando la columna pase a ser la fuente canónica
 * (revisión 01 del Constructor) este test cambia de sentido, no desaparece:
 * lo que se protege es que las tres superficies coincidan con el resto.
 */
class DatosNegocioFuenteUnicaTest extends TestCase
{
    use RefreshDatabase;

    /** Columnas con datos VIEJOS; ajustes con los datos NUEVOS del Constructor. */
    private function tienda(string $plantilla, string $slug): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Datos', 'slug' => $slug, 'is_active' => true,
            'phone' => '111000111', 'whatsapp' => '111000222', 'address' => 'Dirección VIEJA 100',
        ]);
        $project->settings()->createMany([
            ['key' => 'catalog_template', 'value' => $plantilla],
            ['key' => 'contact_phone', 'value' => '999888777'],
            ['key' => 'quote_whatsapp', 'value' => '999888666'],
            ['key' => 'contact_address', 'value' => 'Av. NUEVA 456'],
        ]);
        StorefrontSections::ensure($project);
        StorefrontNavigation::ensure($project);

        return $project;
    }

    /** Estructura V2: encabezado y pie respetan lo escrito en el Constructor. */
    public function test_v2_muestra_los_datos_del_constructor_no_los_de_las_columnas(): void
    {
        $project = $this->tienda('servicios', 'tienda-datos-v2');
        $project->settings()->create(['key' => 'storefront_structure_v2', 'value' => '1']);
        $project->settings()->create(['key' => 'header_show_contact', 'value' => '1']);

        $html = $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();

        $this->assertStringContainsString('999888777', $html, 'Teléfono del Constructor.');
        $this->assertStringContainsString('999888666', $html, 'WhatsApp del Constructor.');
        $this->assertStringContainsString('Av. NUEVA 456', $html, 'Dirección del Constructor.');
        $this->assertStringNotContainsString('Dirección VIEJA 100', $html, 'La columna no debe ganar.');
        $this->assertStringNotContainsString('111000111', $html, 'El teléfono viejo no debe salir.');
    }

    /** Sin ajustes, la columna sigue siendo el respaldo: nadie se queda sin datos. */
    public function test_v2_cae_a_la_columna_cuando_no_hay_ajuste(): void
    {
        $project = $this->tienda('servicios', 'tienda-datos-v2b');
        $project->settings()->whereIn('key', ['contact_phone', 'quote_whatsapp', 'contact_address'])->delete();
        $project->settings()->create(['key' => 'storefront_structure_v2', 'value' => '1']);
        $project->settings()->create(['key' => 'header_show_contact', 'value' => '1']);

        $html = $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();

        $this->assertStringContainsString('111000111', $html, 'Respaldo: teléfono de la columna.');
        $this->assertStringContainsString('Dirección VIEJA 100', $html, 'Respaldo: dirección de la columna.');
    }

    /** El schema que lee Google no puede publicar datos obsoletos. */
    public function test_schema_de_ecommerce_publica_los_datos_vigentes(): void
    {
        $project = $this->tienda('ecommerce', 'tienda-datos-ecom');

        $html = $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m, 'La plantilla emite JSON-LD.');
        $schema = json_decode($m[1], true);

        $this->assertSame('999888777', $schema['telephone'] ?? null, 'Google recibe el teléfono vigente.');
        $this->assertSame('Av. NUEVA 456', $schema['address']['streetAddress'] ?? null, 'Y la dirección vigente.');
        $this->assertSame('999888666', $schema['contactPoint']['telephone'] ?? null, 'Y el WhatsApp vigente.');
    }
}
