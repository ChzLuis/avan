<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\StoreSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El bloque de sedes con mapa.
 *
 * El editor ya existía en el Constructor —dirección, teléfono, horario y un
 * interruptor de mapa— pero solo lo pintaba la plantilla `computienda`. En las
 * demás, el negocio cargaba su local y la tienda no mostraba nada.
 *
 * El mapa usa el incrustado público de Google: no pide clave de API ni cuenta,
 * así que funciona para cualquier negocio sin configurar nada más.
 */
class BloqueSedesTest extends TestCase
{
    use RefreshDatabase;

    private function tiendaConSede(array $sede, array $extra = []): string
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Sedes', 'slug' => 'sed-'.uniqid(), 'is_active' => true,
        ]);
        $project->settings()->create(['key' => 'storefront_structure_v2', 'value' => '1']);

        StoreSection::create([
            'project_id' => $project->id, 'component' => 'locations',
            'content' => array_merge(['title' => 'Nuestros locales', 'items' => [$sede]], $extra),
            'is_enabled' => true, 'sort_order' => 1,
        ]);

        return $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();
    }

    /** La sede sale con su mapa incrustado. */
    public function test_la_sede_sale_con_mapa(): void
    {
        $html = $this->tiendaConSede([
            'enabled' => true, 'name' => 'Tienda Central',
            'address' => 'Av. Grau 123, Lima', 'phone' => '999888777',
            'hours' => 'Lun a Sáb 9:00 - 19:00', 'show_map' => true,
        ]);

        $this->assertStringContainsString('Tienda Central', $html);
        $this->assertStringContainsString('Av. Grau 123, Lima', $html);
        $this->assertStringContainsString('999888777', $html);
        $this->assertStringContainsString('google.com/maps?q=', $html, 'No se incrusta el mapa.');
        $this->assertStringContainsString('output=embed', $html);
        $this->assertStringContainsString('Cómo llegar', $html);
    }

    /** La dirección viaja escapada: una comilla no puede romper el iframe. */
    public function test_la_direccion_va_escapada_en_la_url(): void
    {
        $html = $this->tiendaConSede([
            'enabled' => true, 'name' => 'Sede', 'show_map' => true,
            'address' => 'Jr. "Raro" & Cía #1',
        ]);

        $this->assertStringNotContainsString('q=Jr. "Raro"', $html, 'La dirección no se escapó.');
        $this->assertStringContainsString('%22Raro%22', $html);
    }

    /** Sin mapa se muestran los datos igual: el interruptor manda. */
    public function test_sin_mapa_se_muestran_los_datos(): void
    {
        $html = $this->tiendaConSede([
            'enabled' => true, 'name' => 'Solo Datos',
            'address' => 'Calle Falsa 123', 'show_map' => false,
        ]);

        $this->assertStringContainsString('Solo Datos', $html);
        $this->assertStringNotContainsString('output=embed', $html);
    }

    /** Una sede apagada no se pinta. */
    public function test_una_sede_apagada_no_sale(): void
    {
        $html = $this->tiendaConSede([
            'enabled' => false, 'name' => 'SEDE CERRADA', 'address' => 'Av. X 1',
        ]);

        $this->assertStringNotContainsString('SEDE CERRADA', $html);
    }

    /** La variante «mapa al lado» cambia la disposición. */
    public function test_la_variante_de_mapa_al_lado(): void
    {
        $html = $this->tiendaConSede(
            ['enabled' => true, 'name' => 'Sede', 'address' => 'Av. Uno 1', 'show_map' => true],
            ['variant' => 'map-side']
        );

        $this->assertStringContainsString('sf-loc-grid is-side', $html);
    }

    /** Una sección sin sedes explícitas reutiliza los datos maestros publicados. */
    public function test_una_seccion_sin_items_usa_los_datos_del_negocio(): void
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'IMPORT MURUHUAY SAC',
            'slug' => 'fallback-sede-'.uniqid(),
            'whatsapp' => '908845390',
            'is_active' => true,
        ]);
        $project->settings()->createMany([
            ['key' => 'storefront_structure_v2', 'value' => '1'],
            ['key' => 'catalog_template', 'value' => 'computienda'],
            ['key' => 'contact_address', 'value' => 'Av Guillermo Dansey 444 Psj. 3 AQ-1'],
        ]);

        StoreSection::create([
            'project_id' => $project->id,
            'component' => 'locations',
            'content' => ['title' => 'Visítanos', 'items' => []],
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $html = $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-store-native-section="locations"', $html);
        $this->assertStringContainsString('IMPORT MURUHUAY SAC', $html);
        $this->assertStringContainsString('Av Guillermo Dansey 444 Psj. 3 AQ-1', $html);
        $this->assertStringContainsString('908845390', $html);
        $this->assertStringContainsString('output=embed', $html);
    }
}
