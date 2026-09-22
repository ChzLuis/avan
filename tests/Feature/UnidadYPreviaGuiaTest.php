<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unidad de medida y vista previa de la guía (2026-09-21).
 *
 * Tres fallos que el usuario encontró facturando de verdad:
 *  - Elegía "Caja" y el sistema guardaba "Unidad" a partir del 2.º producto,
 *    porque el catálogo pisaba la unidad elegida (F001-00000025 salió así).
 *  - La guía imprimía el código SUNAT crudo ("BX") donde el cliente espera
 *    leer "Caja"; la factura sí lo traducía.
 *  - La guía se emitía a ciegas: sin vista previa, a diferencia de la factura.
 */
class UnidadYPreviaGuiaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA',
            'slug' => 'up-'.uniqid(), 'is_active' => true,
        ]);
        $m = \App\Models\Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    /** El código de SUNAT es para el XML; en pantalla se lee el nombre. */
    public function test_el_codigo_de_unidad_se_traduce_a_palabras(): void
    {
        $c = \App\Modules\Finanzas\Support\Sunat\Catalogos::class;

        $this->assertSame('Caja', $c::etiquetaUnidad('BX'));
        $this->assertSame('Unidad', $c::etiquetaUnidad('NIU'));
        $this->assertSame('Paquete', $c::etiquetaUnidad('PK'));
        // Un código desconocido o vacío no puede dejar la celda en blanco.
        $this->assertNotSame('', $c::etiquetaUnidad(''));
    }

    /** Las tres representaciones impresas de la guía traducen la unidad. */
    public function test_la_guia_imprime_caja_no_bx(): void
    {
        foreach (['pdf-clasico', 'pdf-simple', 'pdf'] as $vista) {
            $hoja = file_get_contents(base_path("app/Modules/Finanzas/Views/facturacion/guias/{$vista}.blade.php"));

            $this->assertStringContainsString('etiquetaUnidad($item->unit)', $hoja,
                "la vista {$vista} debe traducir la unidad");
            $this->assertStringNotContainsString("{{ \$item->unit ?: 'NIU' }}", $hoja,
                "la vista {$vista} no debe imprimir el codigo crudo");
        }
    }

    /** El catálogo propone la unidad, pero no pisa la que el cajero eligió. */
    public function test_el_catalogo_no_pisa_la_unidad_elegida(): void
    {
        $js = file_get_contents(base_path('app/Modules/Finanzas/Views/invoices/index.blade.php'));

        // Ninguna asignación de unidad puede ir sin guardia.
        foreach (explode("\n", $js) as $n => $linea) {
            if (str_contains($linea, '.unit = ') && ! str_contains($linea, 'unit_price')) {
                $this->assertTrue(
                    str_contains($linea, 'unitTocada') || str_contains($linea, 'unidadPorDefecto'),
                    'la linea '.($n + 1).' pisa la unidad sin comprobar si el cajero la eligio: '.trim($linea)
                );
            }
        }

        $this->assertStringContainsString('unidadPorDefecto(product) {', $js,
            'una linea nueva debe heredar la unidad de la anterior');

        // El select tiene que marcar la elección, o no hay forma de saberlo.
        $form = file_get_contents(base_path('app/Modules/Finanzas/Views/invoices/_formulario.blade.php'));
        $this->assertSame(2, substr_count($form, 'unitTocada = true'),
            'los dos selects (escritorio y movil) deben marcar la eleccion');
    }

    /** La guía se puede revisar antes de emitirla, como la factura. */
    public function test_la_guia_tiene_vista_previa(): void
    {
        $r = $this->comoDueno()->post(route('guias.previsualizar'), [
            'destinatario_nombre' => 'NOEL & CIA S.A.C.',
            'fecha_traslado'      => now()->toDateString(),
            'llegada_direccion'   => 'AV. ARGENTINA 339',
            'items'               => [
                ['description' => 'CINTA AISLANTE 3M', 'quantity' => 50, 'unit' => 'BX'],
                ['description' => '', 'quantity' => 0, 'unit' => 'NIU'],
            ],
        ]);

        $r->assertOk()
          ->assertSee('NOEL &amp; CIA S.A.C.', false)
          ->assertSee('CINTA AISLANTE 3M')
          ->assertSee('Caja');           // traducida, no "BX"
    }

    /** La previa NO graba: no puede gastar un correlativo. */
    public function test_la_vista_previa_no_graba_la_guia(): void
    {
        $antes = \App\Modules\Finanzas\Models\GuiaRemision::count();

        $this->comoDueno()->post(route('guias.previsualizar'), [
            'destinatario_nombre' => 'CLIENTE DE PRUEBA',
            'items'               => [['description' => 'PRODUCTO', 'quantity' => 1, 'unit' => 'BX']],
        ])->assertOk();

        $this->assertSame($antes, \App\Modules\Finanzas\Models\GuiaRemision::count(),
            'la vista previa no debe grabar ninguna guia');
    }

    /**
     * La previa apunta a la ruta de la cara por la que se entro.
     *
     * Yo habia repetido la condicion con $portalLayout dentro del <script> y
     * salia SIEMPRE la del panel: pulsar "Vista previa" entrando por Ventas
     * llamaba a /guias/previsualizar en vez de /bixosales/guias/previsualizar.
     */
    public function test_la_previa_usa_la_ruta_del_portal_de_entrada(): void
    {
        $vista = file_get_contents(base_path('app/Modules/Finanzas/Views/facturacion/guias/index.blade.php'));

        $this->assertStringContainsString("route(\$rutaGuias.'.previsualizar')", $vista,
            'la URL debe salir de \$rutaGuias, como el resto de acciones de la pantalla');
        $this->assertStringNotContainsString("? route('bixosales.guias.previsualizar')", $vista,
            'no se repite la condicion del portal: ya la resuelve \$rutaGuias');
    }

    /** En la guia la unidad elegida tampoco se pisa con la del catalogo. */
    public function test_la_guia_respeta_la_unidad_elegida(): void
    {
        $vista = file_get_contents(base_path('app/Modules/Finanzas/Views/facturacion/guias/index.blade.php'));

        $this->assertStringContainsString('if (!item.unitTocada) item.unit', $vista,
            'el catalogo propone la unidad, no la impone');
        $this->assertStringContainsString('unitTocada = true', $vista,
            'el select debe marcar que el operador eligio');
        $this->assertStringContainsString('unitTocada: false', $vista,
            'las lineas nuevas nacen con el marcador');
    }

    /** El botón existe en la pantalla de emisión. */
    public function test_el_boton_de_previa_esta_en_la_pantalla(): void
    {
        $html = $this->comoDueno()->get(route('guias.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Vista previa', $html);
        $this->assertStringContainsString('verPrevia()', $html);
    }
}
