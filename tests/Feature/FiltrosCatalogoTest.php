<?php

namespace Tests\Feature;

use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los filtros del catálogo, solos y combinados (2026-09-07).
 *
 * Cada filtro reduce el catálogo por su cuenta y, juntos, se INTERSECAN:
 * categoría + marca + precio devuelven solo lo que cumple las tres. Dos
 * valores del mismo filtro (dos marcas) se SUMAN. Es lo que el usuario pidió
 * validar y lo que la interfaz promete con los conteos.
 */
class FiltrosCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private Project $p;
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Filtros QA', 'slug' => 'flt-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['storefront_structure_v2' => '1', 'catalog_template' => 'computienda', 'store_mode' => 'direct'] as $k => $v) {
            $this->p->settings()->create(['key' => $k, 'value' => $v]);
        }
        $cables = Category::create(['project_id' => $this->p->id, 'name' => 'Cables', 'slug' => 'cables', 'is_active' => true]);
        $luces  = Category::create(['project_id' => $this->p->id, 'name' => 'Luces', 'slug' => 'luces', 'is_active' => true]);

        $lista  = CatalogList::create(['project_id' => $this->p->id, 'name' => 'Marcas', 'type' => 'brand', 'is_active' => true]);
        $indeco = CatalogValue::create(['catalog_list_id' => $lista->id, 'label' => 'INDECO', 'code' => 'indeco', 'is_active' => true, 'sort_order' => 1]);
        $tresm  = CatalogValue::create(['catalog_list_id' => $lista->id, 'label' => '3M', 'code' => '3m', 'is_active' => true, 'sort_order' => 2]);

        $color = ProductAttribute::create(['project_id' => $this->p->id, 'name' => 'Color', 'slug' => 'color', 'type' => 'color', 'is_variant' => false, 'is_filterable' => true, 'is_active' => true, 'sort_order' => 1]);
        $rojo  = ProductAttributeValue::create(['project_id' => $this->p->id, 'product_attribute_id' => $color->id, 'label' => 'Rojo', 'value' => 'rojo', 'color_hex' => '#ff0000', 'is_active' => true, 'sort_order' => 1]);
        $azul  = ProductAttributeValue::create(['project_id' => $this->p->id, 'product_attribute_id' => $color->id, 'label' => 'Azul', 'value' => 'azul', 'color_hex' => '#0000ff', 'is_active' => true, 'sort_order' => 2]);

        $crear = fn (string $n, Category $c, ?CatalogValue $m, float $precio, int $stock, ?float $antes = null) => Product::create([
            'project_id' => $this->p->id, 'category_id' => $c->id, 'brand_catalog_id' => $m?->id, 'name' => $n,
            'sku' => strtoupper(substr(md5($n), 0, 8)), 'price' => $precio, 'compare_price' => $antes, 'stock' => $stock, 'is_available' => true,
        ]);
        // 6 productos con combinaciones distintas.
        $a = $crear('Cable THHN rojo INDECO', $cables, $indeco, 50, 10);
        $b = $crear('Cable NH-80 azul INDECO', $cables, $indeco, 120, 0);
        $c = $crear('Cinta aislante 3M', $cables, $tresm, 8, 30);
        $d = $crear('Foco LED INDECO', $luces, $indeco, 25, 5, 40);   // en oferta
        $e = $crear('Reflector 3M', $luces, $tresm, 300, 2);
        $f = $crear('Luminaria sin marca', $luces, null, 90, 1);

        $a->attributeValues()->attach($rojo->id, ['project_id' => $this->p->id, 'product_attribute_id' => $color->id]);
        $b->attributeValues()->attach($azul->id, ['project_id' => $this->p->id, 'product_attribute_id' => $color->id]);
        $d->attributeValues()->attach($rojo->id, ['project_id' => $this->p->id, 'product_attribute_id' => $color->id]);

        $this->ids = compact('cables', 'luces', 'indeco', 'tresm', 'color', 'rojo', 'azul');
    }

    private function nombres(array $params): array
    {
        $params['format'] = 'json';
        $r = $this->getJson(route('public.shop', $this->p->slug).'?'.http_build_query($params));
        $r->assertOk();

        return collect($r->json('products'))->pluck('name')->sort()->values()->all();
    }

    public function test_cada_filtro_funciona_por_separado(): void
    {
        $this->assertCount(6, $this->nombres([]));
        $this->assertSame(['Cable NH-80 azul INDECO', 'Cable THHN rojo INDECO', 'Cinta aislante 3M'], $this->nombres(['category' => [$this->ids['cables']->id]]));
        $this->assertSame(['Cable NH-80 azul INDECO', 'Cable THHN rojo INDECO', 'Foco LED INDECO'], $this->nombres(['brand' => [$this->ids['indeco']->id]]));
        $this->assertSame(['Cable THHN rojo INDECO', 'Foco LED INDECO', 'Luminaria sin marca'], $this->nombres(['min_price' => 20, 'max_price' => 100]));
        $this->assertNotContains('Cable NH-80 azul INDECO', $this->nombres(['in_stock' => 1]), 'sin stock no entra en "en stock"');
        $this->assertSame(['Foco LED INDECO'], $this->nombres(['sale' => 1]));
        // La busqueda tambien mira la categoria: "cable" trae la cinta de la categoria Cables.
        $this->assertSame(['Cable NH-80 azul INDECO', 'Cable THHN rojo INDECO', 'Cinta aislante 3M'], $this->nombres(['q' => 'cable']));
        $this->assertSame(['Cable NH-80 azul INDECO'], $this->nombres(['q' => 'NH-80']));
        $this->assertSame(['Cable THHN rojo INDECO', 'Foco LED INDECO'], $this->nombres(['attribute' => [$this->ids['color']->id => [$this->ids['rojo']->id]]]));
    }

    public function test_los_filtros_combinados_se_intersecan(): void
    {
        // categoría + marca
        $this->assertSame(['Cable NH-80 azul INDECO', 'Cable THHN rojo INDECO'], $this->nombres(['category' => [$this->ids['cables']->id], 'brand' => [$this->ids['indeco']->id]]));
        // categoría + marca + precio
        $this->assertSame(['Cable THHN rojo INDECO'], $this->nombres(['category' => [$this->ids['cables']->id], 'brand' => [$this->ids['indeco']->id], 'max_price' => 100]));
        // marca + en stock
        $this->assertSame(['Cable THHN rojo INDECO', 'Foco LED INDECO'], $this->nombres(['brand' => [$this->ids['indeco']->id], 'in_stock' => 1]));
        // búsqueda + atributo
        $this->assertSame(['Cable THHN rojo INDECO'], $this->nombres(['q' => 'THHN', 'attribute' => [$this->ids['color']->id => [$this->ids['rojo']->id]]]));
        // categoría + marca sin coincidencias = vacío, no "todo"
        $this->assertSame([], $this->nombres(['category' => [$this->ids['luces']->id], 'brand' => [$this->ids['tresm']->id], 'max_price' => 50]));
    }

    public function test_dos_valores_del_mismo_filtro_se_suman(): void
    {
        $this->assertCount(5, $this->nombres(['brand' => [$this->ids['indeco']->id, $this->ids['tresm']->id]]));
        $this->assertCount(3, $this->nombres(['attribute' => [$this->ids['color']->id => [$this->ids['rojo']->id, $this->ids['azul']->id]]]));
    }
}
