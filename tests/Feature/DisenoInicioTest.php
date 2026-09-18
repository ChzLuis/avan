<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Modules\Tienda\Models\StoreSection;
use App\Models\User;
use App\Modules\Tienda\Storefront\HomePresets;
use App\Modules\Tienda\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los cinco Diseños de Inicio.
 *
 * La promesa que el negocio necesita poder creer es una sola: puedo probar
 * los cinco diseños sin miedo, porque ninguno borra nada y siempre puedo
 * volver. Todo lo que se prueba aquí defiende esa promesa.
 *
 * Es la misma familia de fallo que ya nos mordió con la analítica, el menú y
 * los bloques de Inicio: un ajuste que existe, se guarda y no llega. Aquí se
 * comprueba además el camino completo — que la clave tenga editor, que llegue
 * a la tienda y que la tienda la pinte.
 */
class DisenoInicioTest extends TestCase
{
    use RefreshDatabase;

    /** Colección de secciones falsas, una por componente del registro. */
    private function secciones(): \Illuminate\Support\Collection
    {
        return collect(array_keys(StorefrontSections::COMPONENTS))
            ->map(fn ($c) => (object) ['component' => $c]);
    }

    /** Cambiar de diseño no pierde NI UN bloque: solo cambia el orden. */
    public function test_ningun_preset_pierde_bloques(): void
    {
        $todos = array_keys(StorefrontSections::COMPONENTS);

        foreach (array_keys(HomePresets::todos()) as $clave) {
            $salida = HomePresets::ordenar($this->secciones(), $clave)
                ->pluck('component')->all();

            sort($todos);
            $ordenada = $salida;
            sort($ordenada);

            $this->assertSame($todos, $ordenada,
                "El diseño {$clave} pierde o duplica bloques.");
        }
    }

    /**
     * Ir y volver devuelve exactamente la misma portada.
     *
     * Sin esto el orden dependía de por qué diseños habías pasado antes:
     * 1 → 4 → 2 → 1 no daba lo mismo que el 1 directo. Se arregló anclando la
     * cola al registro canónico en vez de a la posición de entrada.
     */
    public function test_cambiar_de_diseno_es_reversible(): void
    {
        $directo = HomePresets::ordenar($this->secciones(), '1')->pluck('component')->all();

        $ida = HomePresets::ordenar($this->secciones(), '4');
        $vuelta = HomePresets::ordenar(HomePresets::ordenar($ida, '2'), '1')->pluck('component')->all();

        $this->assertSame($directo, $vuelta,
            'Pasar por otros diseños y volver cambia la portada.');
    }

    /** Una tienda anterior a esto (sin la clave) se comporta como el diseño 01. */
    public function test_tienda_antigua_cae_al_diseno_uno(): void
    {
        $this->assertSame(HomePresets::POR_DEFECTO, HomePresets::clave(null));
        $this->assertSame(
            HomePresets::ordenar($this->secciones(), '1')->pluck('component')->all(),
            HomePresets::ordenar($this->secciones(), null)->pluck('component')->all(),
        );
    }

    /** Un valor inventado o corrupto nunca rompe la tienda. */
    public function test_valores_invalidos_caen_al_defecto(): void
    {
        foreach ([null, '', '0', '9', 'x', '<script>', 999] as $basura) {
            $this->assertArrayHasKey(HomePresets::clave($basura), HomePresets::todos());
        }
        foreach ([null, '', 'zzz', '<b>'] as $basura) {
            $this->assertContains(HomePresets::variante($basura), ['a', 'b']);
        }
    }

    /** Una tienda a medio llenar no revienta ni deja huecos. */
    public function test_tiendas_incompletas(): void
    {
        $this->assertCount(0, HomePresets::ordenar(collect(), '3'));

        $pocos = collect([(object) ['component' => 'hero'], (object) ['component' => 'featured_products']]);
        $this->assertSame(['hero', 'featured_products'],
            HomePresets::ordenar($pocos, '5')->pluck('component')->all());
    }

    /** Los cinco diseños se pueden elegir desde el Constructor. */
    public function test_hay_editor_para_las_dos_claves(): void
    {
        $editor = file_get_contents(app_path('Modules/Tienda/Views/settings/builder/stages/home.blade.php'));

        $this->assertStringContainsString("setSetting('home_template'", $editor,
            'Sin editor, el diseño de Inicio no se puede elegir.');
        $this->assertStringContainsString("setSetting('home_hero_variant'", $editor,
            'Falta el selector de variante de portada.');
    }

    /** Y cada diseño ofrecido tiene su piel: si no, se elige y no cambia nada. */
    public function test_cada_diseno_tiene_su_piel_visual(): void
    {
        $piel = file_get_contents(app_path('Modules/Tienda/Views/components/storefront-home-skins.blade.php'));

        foreach (array_keys(HomePresets::todos()) as $clave) {
            $this->assertStringContainsString(".hp-{$clave} ", $piel,
                "El diseño {$clave} no tiene reglas visuales propias.");
        }
        $this->assertStringContainsString('.hp-b ', $piel, 'Falta la variante B de portada.');
    }

    /**
     * El camino completo: se guarda, llega a la tienda y la tienda lo pinta.
     *
     * Es la comprobación que cierra la puerta al fallo de siempre — el ajuste
     * que existe, se guarda y nunca llega al storefront.
     */
    public function test_el_diseno_elegido_llega_a_la_tienda(): void
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Diseño', 'slug' => 'dis-'.uniqid(), 'is_active' => true,
        ]);
        $project->settings()->create(['key' => 'storefront_structure_v2', 'value' => '1']);
        $project->settings()->create(['key' => 'home_template', 'value' => '4']);
        $project->settings()->create(['key' => 'home_hero_variant', 'value' => 'b']);

        // Un bloque que se pinta con su propio contenido: `featured_categories`
        // no sirve aquí porque depende de que existan categorías reales.
        StoreSection::create([
            'project_id' => $project->id, 'component' => 'faq',
            'content' => ['title' => 'PREGUNTAS BLOQUE',
                'items' => [['enabled' => true, 'question' => '¿Envían?', 'answer' => 'Sí']]],
            'is_enabled' => true, 'sort_order' => 1,
        ]);

        $html = $this->get(route('public.catalog', $project->slug))->assertOk()->getContent();

        $this->assertStringContainsString("'hp-4'", $html, 'La tienda no aplica el diseño elegido.');
        $this->assertStringContainsString("'hp-b'", $html, 'La tienda no aplica la variante de portada.');
        $this->assertStringContainsString('PREGUNTAS BLOQUE', $html, 'Se perdió el contenido del bloque.');
    }
}
