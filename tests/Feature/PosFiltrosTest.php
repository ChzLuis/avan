<?php

namespace Tests\Feature;

use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtros del Punto de Venta (2026-09-11): categoria -> subcategoria -> marca.
 * El servidor tiene que entregar marca, SKU y padre de cada categoria, o los
 * chips no tienen con que filtrar.
 */
class PosFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA', 'slug' => 'pos-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['orders', 'catalog'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        $raiz = Category::create(['project_id' => $this->project->id, 'name' => 'Conductores eléctricos', 'slug' => 'cond', 'is_active' => true, 'sort_order' => 1]);
        $sub  = Category::create(['project_id' => $this->project->id, 'name' => 'Cables THW-90', 'slug' => 'thw', 'is_active' => true, 'parent_id' => $raiz->id, 'sort_order' => 2]);
        $lista = CatalogList::create(['project_id' => $this->project->id, 'name' => 'Marcas', 'type' => 'brand', 'is_active' => true]);
        $indeco = CatalogValue::create(['catalog_list_id' => $lista->id, 'label' => 'INDECO', 'code' => 'indeco', 'is_active' => true, 'sort_order' => 1]);

        Product::create(['project_id' => $this->project->id, 'category_id' => $sub->id, 'brand_catalog_id' => $indeco->id,
            'name' => 'CABLE THW-90 N12', 'sku' => 'THW-12', 'price' => 232, 'is_available' => true]);
    }

    public function test_el_pos_entrega_marca_sku_y_padre_de_categoria(): void
    {
        $html = $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id])
            ->get(route('pos.index'))->assertOk()->getContent();

        $this->assertStringContainsString('"brand":"INDECO"', $html, 'la marca viaja con el producto');
        $this->assertStringContainsString('"sku":"THW-12"', $html, 'el SKU viaja: se busca por el');
        $this->assertStringContainsString('"parent_id":', $html, 'las categorias llevan su padre para agrupar sub-chips');
        // Las piezas de la barra de filtros.
        foreach (['catRaices', 'marcasDisponibles', 'filterBrand', 'limpiarFiltros()', 'aria-label="Categoría"', 'aria-label="Marca"', 'elegirDesdeSelect('] as $pieza) {
            $this->assertStringContainsString($pieza, $html, "falta $pieza en el POS");
        }
    }
}
