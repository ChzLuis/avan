<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El buscador predictivo del detalle del comprobante (2026-09-09).
 *
 * Al escribir en "Descripción" debe desplegarse la lista de productos del
 * negocio. Si el catálogo no viaja a la vista, el campo queda mudo y hay que
 * teclear cada línea a mano.
 */
class BuscadorProductosComprobanteTest extends TestCase
{
    use RefreshDatabase;

    private function negocio(): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Ferretería QA', 'slug' => 'buz-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['facturacion', 'catalog'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => ucfirst($key)]);
            $p->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        return $p;
    }

    private function html(Project $p): string
    {
        return $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->get(route('invoices.index'))->assertOk()->getContent();
    }

    /** El catálogo del negocio viaja a la pantalla del comprobante. */
    public function test_el_catalogo_llega_al_formulario(): void
    {
        $p = $this->negocio();
        Product::create([
            'project_id' => $p->id, 'name' => 'ROLLO DE CABLE MELLIZO 2x18',
            'sku' => 'CAB-218', 'price' => 120.50, 'is_available' => true,
        ]);

        $html = $this->html($p);

        $this->assertStringContainsString('ROLLO DE CABLE MELLIZO 2x18', $html,
            'el producto no llegó al catálogo de la pantalla');
        $this->assertStringContainsString('CAB-218', $html, 'el SKU debe viajar: se busca por él');
        // Sin estas piezas el desplegable no se arma.
        $this->assertStringContainsString('searchCatalog(idx)', $html);
        $this->assertStringContainsString('item.showSuggestions', $html);
    }

    /** Un producto no disponible no se ofrece para facturar. */
    public function test_lo_no_disponible_no_se_ofrece(): void
    {
        $p = $this->negocio();
        Product::create([
            'project_id' => $p->id, 'name' => 'DESCATALOGADO SA',
            'price' => 10, 'is_available' => false,
        ]);

        $this->assertStringNotContainsString('DESCATALOGADO SA', $this->html($p));
    }

    /** El catálogo de un negocio no se cuela en la pantalla de otro. */
    public function test_no_se_cruzan_los_negocios(): void
    {
        $mio = $this->negocio();
        $ajeno = $this->negocio();
        Product::create([
            'project_id' => $ajeno->id, 'name' => 'PRODUCTO AJENO SAC',
            'price' => 50, 'is_available' => true,
        ]);

        $this->assertStringNotContainsString('PRODUCTO AJENO SAC', $this->html($mio));
    }

    /**
     * Carga de corrido: elegir producto lleva a Cantidad y Enter abre la
     * siguiente linea, sin tocar el raton.
     */
    public function test_se_puede_tabular_de_corrido(): void
    {
        $p = $this->negocio();
        Product::create(['project_id' => $p->id, 'name' => 'CABLE TW 12', 'price' => 90, 'is_available' => true]);

        $html = $this->html($p);

        // El foco salta solo: sin estas referencias no hay a donde saltar.
        // Alpine 3 no admite `:ref` dinamico: el salto se hace por atributo de datos.
        $this->assertStringContainsString(':data-cantidad="idx"', $html);
        $this->assertStringContainsString(':data-descripcion="idx"', $html);
        $this->assertStringContainsString('enfocarCampo(', $html);
        $this->assertStringContainsString('siguienteLinea(idx)', $html);
        // Y la cantidad se selecciona al entrar, para sobrescribir el 1 de una.
        $this->assertStringContainsString('$event.target.select()', $html);
    }

    /** La rejilla abre con 5 renglones listos, como una hoja de calculo. */
    public function test_abre_con_cinco_renglones(): void
    {
        $html = $this->html($this->negocio());

        $this->assertStringContainsString('[1,2,3,4,5].map(function () { return {', $html,
            'el formulario debe arrancar con cinco lineas en blanco');
        // Y las que queden vacias no se mandan al servidor.
        $this->assertStringContainsString("filter(i => (i.description || '').trim() !== '')", $html);
    }

    /** La rejilla se ve como una hoja de calculo: celdas con borde, sin huecos. */
    public function test_la_rejilla_parece_una_hoja_de_calculo(): void
    {
        $html = $this->html($this->negocio());

        $this->assertStringContainsString('fac-hoja', $html);
        $this->assertStringContainsString('border-collapse: collapse', $html);
        // El campo llena su celda: sin borde propio ni esquinas redondeadas.
        $this->assertStringContainsString('border: 0; border-radius: 0;', $html);
    }

    /** La rejilla no crece sin fin: 5 renglones y luego scrollea. */
    public function test_la_rejilla_tiene_tope_de_alto(): void
    {
        $html = $this->html($this->negocio());

        $this->assertStringContainsString('fac-rejilla', $html);
        $this->assertStringContainsString('max-height: 268px', $html, '5 renglones visibles');
        $this->assertStringContainsString('position: sticky', $html, 'la cabecera se queda fija al scrollear');
    }

    /** Un negocio sin productos no rompe la pantalla: el catálogo va vacío. */
    public function test_sin_productos_la_pantalla_abre_igual(): void
    {
        $html = $this->html($this->negocio());

        $this->assertStringContainsString('catalogo:', $html);
        $this->assertStringContainsString('searchCatalog(idx)', $html);
    }
}
