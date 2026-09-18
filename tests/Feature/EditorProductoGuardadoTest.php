<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Category;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El editor de productos guarda lo que el mismo editor pinta (2026-09-07).
 *
 * Reproduce el flujo real: se abre la pagina, se toma el producto tal como la
 * pagina lo entrega a Alpine (`__productPageData.products`) y se manda de
 * vuelta con PUT sin tocar nada. Si eso da 422, un cliente no puede guardar
 * ni un producto recien abierto, que fue exactamente el reporte.
 */
class EditorProductoGuardadoTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Distribuidora QA', 'slug' => 'edp-'.uniqid(), 'is_active' => true,
        ]);
        $modulo = \App\Models\Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo']);
        $project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
        foreach ($ajustes as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }

        $raiz = Category::create(['project_id' => $project->id, 'name' => 'ILUMINACIÓN SOLAR', 'slug' => 'ilum-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        Category::create(['project_id' => $project->id, 'name' => 'Pastoral Solar', 'slug' => 'past-'.uniqid(), 'is_active' => true, 'parent_id' => $raiz->id, 'sort_order' => 2]);
        $vieja = Category::create(['project_id' => $project->id, 'name' => 'PASTORAL SOLAR', 'slug' => 'pasv-'.uniqid(), 'is_active' => true, 'sort_order' => 3]);

        // Como los de Musuhuay: precio 0, sin marca, sin ficha.
        Product::create([
            'project_id' => $project->id, 'category_id' => $vieja->id,
            'name' => 'Pastoral solar 200W con panel', 'slug' => 'p-'.uniqid(),
            'price' => 0, 'stock' => 0, 'is_available' => true,
        ]);

        return $project;
    }

    /** Lo que la pagina entrega a Alpine, tal cual. */
    private function datosPagina(Project $project): array
    {
        $html = $this->actingAs($project->owner)->withSession(['active_project_id' => $project->id])
            ->get(route('products.index'))->assertOk()->getContent();

        // Js::from pinta `JSON.parse('<json escapado como cadena JS>')`.
        $this->assertTrue((bool) preg_match("/products:\s*JSON\.parse\('(.*?)'\),\s*categories:\s*JSON\.parse\('(.*?)'\),\s*brands:/s", $html, $m), 'no se hallo __productPageData');
        $decodifica = fn (string $js) => json_decode(json_decode('"'.$js.'"'), true);

        return ['products' => $decodifica($m[1]), 'categories' => $decodifica($m[2])];
    }

    public function test_la_pagina_lista_las_categorias_con_sus_hijas(): void
    {
        $datos = $this->datosPagina($this->tienda());

        $nombres = collect($datos['categories'])->pluck('name')->all();
        $this->assertContains('ILUMINACIÓN SOLAR', $nombres);
        $this->assertContains('PASTORAL SOLAR', $nombres);
        $hijas = collect($datos['categories'])->firstWhere('name', 'ILUMINACIÓN SOLAR')['children'] ?? [];
        $this->assertSame(['Pastoral Solar'], array_column($hijas, 'name'));
    }

    public function test_guardar_el_producto_tal_como_lo_pinta_la_pagina(): void
    {
        $project = $this->tienda();
        $datos = $this->datosPagina($project);
        $form = $datos['products'][0];
        $this->assertNotEmpty($form);

        // Lo mueve a la subcategoria nueva, que es lo que intentaba el cliente.
        $nueva = collect($datos['categories'])->firstWhere('name', 'ILUMINACIÓN SOLAR')['children'][0]['id'];
        $form['category_id'] = (string) $nueva;

        $r = $this->actingAs($project->owner)->withSession(['active_project_id' => $project->id])
            ->putJson(route('products.update', $form['id']), $form);

        $this->assertSame(200, $r->getStatusCode(), 'el servidor rechazo el formulario: '.$r->getContent());
        $this->assertSame((int) $nueva, Product::find($form['id'])->category_id);
    }

    public function test_guardar_en_tienda_por_cotizacion(): void
    {
        $project = $this->tienda(['store_mode' => 'quote', 'quote_price_display' => 'hide']);
        $datos = $this->datosPagina($project);
        $form = $datos['products'][0];

        $r = $this->actingAs($project->owner)->withSession(['active_project_id' => $project->id])
            ->putJson(route('products.update', $form['id']), $form);

        $this->assertSame(200, $r->getStatusCode(), 'el servidor rechazo el formulario: '.$r->getContent());
    }

    public function test_crear_un_producto_desde_el_formulario_vacio(): void
    {
        $project = $this->tienda();
        // El formulario en blanco del editor (linea `blank()` del componente).
        $form = ['name' => 'Nuevo reflector', 'sku' => '', 'barcode' => '', 'description' => '', 'notes' => '',
            'ficha_tecnica_archivo' => null, 'ficha_tecnica_url' => '', 'destacados' => [],
            'price' => '', 'price_suggested' => '', 'price_min' => '', 'price_max' => '', 'compare_price' => '', 'wholesale_price' => '',
            'wholesale_min_qty' => '', 'wholesale_unit' => '', 'cost' => '', 'unit' => '', 'sizes' => '', 'etiquetas' => '',
            'stock' => 0, 'stock_min' => 0, 'stock_max' => 0, 'location' => '', 'supplier' => '',
            'has_tax' => false, 'tax_rate' => 18, 'is_available' => true, 'category_id' => '', 'brand_catalog_id' => ''];

        $r = $this->actingAs($project->owner)->withSession(['active_project_id' => $project->id])
            ->postJson(route('products.store'), $form);

        $this->assertSame(200, $r->getStatusCode(), 'el servidor rechazo el formulario: '.$r->getContent());
        $this->assertSame('0.00', (string) Product::where('name', 'Nuevo reflector')->value('price'), 'sin precio se guarda 0');
    }

    /** Cuando el servidor rechaza, el aviso sale en castellano y con el campo claro. */
    public function test_los_errores_de_validacion_salen_en_castellano(): void
    {
        $project = $this->tienda();
        $form = ['name' => str_repeat('x', 120), 'price' => '', 'ficha_tecnica_url' => 'www.sin-esquema.com'];

        $r = $this->actingAs($project->owner)->withSession(['active_project_id' => $project->id])
            ->postJson(route('products.store'), $form);

        $r->assertStatus(422);
        $errores = collect($r->json('errors'))->flatten()->implode(' ');
        $this->assertStringContainsString('El nombre no puede pasar de 100 caracteres', $errores);
        $this->assertStringContainsString('http:// o https://', $errores);
    }

    /** El selector de categoria del editor lleva las opciones ya pintadas (con hijas). */
    public function test_el_selector_de_categoria_trae_las_hijas_pintadas(): void
    {
        $project = $this->tienda();
        $html = $this->actingAs($project->owner)->withSession(['active_project_id' => $project->id])
            ->get(route('products.index'))->getContent();

        $hija = Category::where('project_id', $project->id)->whereNotNull('parent_id')->first();
        $this->assertStringContainsString('<optgroup label="ILUMINACIÓN SOLAR">', $html);
        $this->assertStringContainsString('<option value="'.$hija->id.'">&nbsp;&nbsp;└ Pastoral Solar</option>', $html);
    }
}
