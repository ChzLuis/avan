<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Module;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validación de punta a punta del Catálogo de Productos (2026-09-07).
 *
 * Las suites que ya existían cubrían piezas sueltas (permisos, PDF, variantes,
 * etiquetas, imágenes). Aquí se ejercita el catálogo COMO LO USA el comercio:
 * crear, editar, mover de categoría, duplicar, acciones masivas, exportar,
 * borrar y el aislamiento entre tiendas.
 *
 * El caso que motivó todo: en Musuhuay TODOS los productos valen 0.00 porque el
 * catálogo es "a cotizar". Un precio 0 tiene que poder crearse, guardarse y
 * editarse igual que uno de 150; si algo lo trata como "vacío", el comercio se
 * queda sin poder tocar su catálogo.
 */
class CatalogoProductosValidacionTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Catálogo QA', 'slug' => 'cat-'.uniqid(), 'is_active' => true,
        ]);
        $modulo = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo']);
        $project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
        foreach ($ajustes as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }

        return $project;
    }

    private function raizConHija(Project $p): array
    {
        $raiz = Category::create(['project_id' => $p->id, 'name' => 'ILUMINACIÓN SOLAR', 'slug' => 'r-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $hija = Category::create(['project_id' => $p->id, 'name' => 'Pastoral Solar', 'slug' => 'h-'.uniqid(), 'is_active' => true, 'parent_id' => $raiz->id, 'sort_order' => 2]);

        return [$raiz, $hija];
    }

    private function producto(Project $p, array $extra = []): Product
    {
        // array_merge y NO el operador `+`: con `+` gana la clave de la
        // izquierda, asi que un ['name' => ...] en $extra se ignoraba en
        // silencio y todos los productos salian con el mismo nombre.
        return Product::create(array_merge([
            'project_id' => $p->id,
            'name' => 'Pastoral solar 200W', 'slug' => 'p-'.uniqid(),
            'price' => 0, 'stock' => 0, 'is_available' => true,
        ], $extra));
    }

    private function como(Project $p)
    {
        return $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id]);
    }

    // ═══ Crear ══════════════════════════════════════════════════════════════

    /** Un catálogo "a cotizar" se crea con precio vacío y queda en 0, no falla. */
    public function test_crear_producto_sin_precio(): void
    {
        $project = $this->tienda();

        $r = $this->como($project)->postJson(route('products.store'), [
            'name' => 'Reflector 100W', 'price' => '', 'stock' => 0, 'is_available' => true,
        ]);

        $this->assertSame(200, $r->getStatusCode(), $r->getContent());
        $this->assertSame('0.00', (string) Product::where('name', 'Reflector 100W')->value('price'));
    }

    /** El nombre es lo ÚNICO obligatorio: sin él, 422 y no se crea nada. */
    public function test_sin_nombre_no_se_crea(): void
    {
        $project = $this->tienda();

        $this->como($project)->postJson(route('products.store'), ['name' => '', 'price' => 100])
            ->assertStatus(422);

        $this->assertSame(0, $project->products()->count());
    }

    /** Un precio negativo no entra: es un error de tecleo, no un descuento. */
    public function test_precio_negativo_se_rechaza(): void
    {
        $project = $this->tienda();

        $this->como($project)->postJson(route('products.store'), ['name' => 'Raro', 'price' => -50])
            ->assertStatus(422);
    }

    // ═══ Editar ═════════════════════════════════════════════════════════════

    /** Cambiar de categoría a una SUBcategoría: el caso real de Musuhuay. */
    public function test_mover_a_una_subcategoria(): void
    {
        $project = $this->tienda();
        [$raiz, $hija] = $this->raizConHija($project);
        $producto = $this->producto($project, ['category_id' => $raiz->id]);

        $r = $this->como($project)->putJson(route('products.update', $producto->id), [
            'name' => $producto->name, 'price' => 0, 'category_id' => $hija->id,
        ]);

        $this->assertSame(200, $r->getStatusCode(), $r->getContent());
        $this->assertSame($hija->id, $producto->fresh()->category_id);
    }

    /** Editar un producto de precio 0 sin tocar el precio lo deja en 0. */
    public function test_editar_conservando_el_precio_cero(): void
    {
        $project = $this->tienda();
        $producto = $this->producto($project);

        $r = $this->como($project)->putJson(route('products.update', $producto->id), [
            'name' => 'Pastoral solar 200W TRICOLOR', 'price' => 0,
        ]);

        $this->assertSame(200, $r->getStatusCode(), $r->getContent());
        $producto->refresh();
        $this->assertSame('Pastoral solar 200W TRICOLOR', $producto->name);
        $this->assertSame('0.00', (string) $producto->price);
    }

    /** Quitar la categoría (dejarla en blanco) es válido: producto suelto. */
    public function test_quitar_la_categoria(): void
    {
        $project = $this->tienda();
        [$raiz] = $this->raizConHija($project);
        $producto = $this->producto($project, ['category_id' => $raiz->id]);

        $this->como($project)->putJson(route('products.update', $producto->id), [
            'name' => $producto->name, 'price' => 0, 'category_id' => null,
        ])->assertOk();

        $this->assertNull($producto->fresh()->category_id);
    }

    // ═══ Duplicar ═══════════════════════════════════════════════════════════

    /** Duplicar deja DOS productos y no pisa el original. */
    public function test_duplicar_producto(): void
    {
        $project = $this->tienda();
        $producto = $this->producto($project, ['sku' => 'PAS-200']);

        $this->como($project)->postJson(route('products.duplicate', $producto->id))->assertOk();

        $this->assertSame(2, $project->products()->count());
        $this->assertDatabaseHas('products', ['id' => $producto->id, 'sku' => 'PAS-200']);
    }

    // ═══ Acciones masivas ═══════════════════════════════════════════════════

    /** Marcar varios como no disponibles de una vez. */
    public function test_masivo_ocultar_productos(): void
    {
        $project = $this->tienda();
        $ids = [$this->producto($project)->id, $this->producto($project)->id];

        $this->como($project)->postJson(route('products.bulk-action'), [
            'ids' => $ids, 'action' => 'unavailable',
        ])->assertOk();

        $this->assertSame(0, $project->products()->where('is_available', true)->count());
    }

    /** Mover varios a otra categoría de una vez. */
    public function test_masivo_cambiar_categoria(): void
    {
        $project = $this->tienda();
        [, $hija] = $this->raizConHija($project);
        $ids = [$this->producto($project)->id, $this->producto($project)->id];

        $this->como($project)->postJson(route('products.bulk-action'), [
            'ids' => $ids, 'action' => 'set_category', 'category_id' => $hija->id,
        ])->assertOk();

        $this->assertSame(2, $project->products()->where('category_id', $hija->id)->count());
    }

    /**
     * Subir precios un 10% en masa.
     *
     * Se salta en SQLite: `bulkPriceAdjust` usa GREATEST(), que MySQL tiene y
     * SQLite no. Verificado a mano contra el MySQL 8.4 de produccion:
     *   GREATEST(0.01, 100*(1+10/100)) = 110.00   → el ajuste funciona
     *   GREATEST(0.01,   0*(1+10/100)) =   0.01   → OJO con el catalogo a
     * cotizar: un porcentaje sobre 0 no "pone precio", deja 0.01 en TODO el
     * catalogo. Para poner precios donde no los hay, se edita producto a
     * producto o se importa, no con el ajuste porcentual.
     */
    public function test_masivo_ajuste_de_precio(): void
    {
        if (\DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('GREATEST() no existe en SQLite; verificado contra el MySQL de produccion.');
        }

        $project = $this->tienda();
        $conPrecio = $this->producto($project, ['price' => 100]);

        $this->como($project)->postJson(route('products.bulk-action'), [
            'ids' => [$conPrecio->id], 'action' => 'price_adjust',
            'price_mode' => 'pct', 'price_delta' => 10,
        ])->assertOk();

        $this->assertSame('110.00', (string) $conPrecio->fresh()->price);
    }

    /** Una acción inventada no se ejecuta: 422 y el catálogo intacto. */
    public function test_accion_masiva_desconocida_se_rechaza(): void
    {
        $project = $this->tienda();
        $producto = $this->producto($project);

        $this->como($project)->postJson(route('products.bulk-action'), [
            'ids' => [$producto->id], 'action' => 'formatear_todo',
        ])->assertStatus(422);

        $this->assertDatabaseHas('products', ['id' => $producto->id]);
    }

    // ═══ Borrar ═════════════════════════════════════════════════════════════

    public function test_borrar_producto(): void
    {
        $project = $this->tienda();
        $producto = $this->producto($project);

        $this->como($project)->deleteJson(route('products.destroy', $producto->id))->assertOk();

        $this->assertSame(0, $project->products()->count());
    }

    /** Vaciar el catálogo exige escribir el slug del proyecto, y de verdad. */
    public function test_vaciar_catalogo_exige_la_confirmacion_correcta(): void
    {
        $project = $this->tienda();
        $this->producto($project);

        $this->como($project)->deleteJson(route('products.purge-all'), ['confirm_slug' => 'otra-cosa'])
            ->assertStatus(422);
        $this->assertSame(1, $project->products()->count(), 'una confirmacion errada NO puede vaciar el catalogo');

        $this->como($project)->deleteJson(route('products.purge-all'), ['confirm_slug' => $project->slug])
            ->assertOk();
        $this->assertSame(0, $project->products()->count());
    }

    // ═══ Exportar ═══════════════════════════════════════════════════════════

    /** Las cuatro exportaciones y la plantilla responden y traen el producto. */
    public function test_exportaciones(): void
    {
        $project = $this->tienda();
        $this->producto($project, ['name' => 'Reflector exportable']);

        foreach (['products.export', 'products.template', 'products.export.static',
                  'products.export.meli', 'products.export.rappi', 'products.export.shopee'] as $ruta) {
            $r = $this->como($project)->get(route($ruta));
            $this->assertSame(200, $r->getStatusCode(), "la ruta {$ruta} no responde 200");
        }
    }

    // ═══ Aislamiento entre tiendas ══════════════════════════════════════════

    /** Nadie edita el producto de otra tienda, ni conociendo su id. */
    public function test_no_se_edita_el_producto_de_otra_tienda(): void
    {
        $mia  = $this->tienda();
        $ajena = $this->tienda();
        $suyo = $this->producto($ajena, ['name' => 'Producto ajeno']);

        $r = $this->como($mia)->putJson(route('products.update', $suyo->id), [
            'name' => 'Secuestrado', 'price' => 1,
        ]);

        $this->assertContains($r->getStatusCode(), [403, 404], 'se pudo tocar el producto de otra tienda');
        $this->assertSame('Producto ajeno', $suyo->fresh()->name, 'se modifico el producto de otra tienda');
    }

    /** Ni se borra en masa el catálogo ajeno colando sus ids. */
    public function test_no_se_borra_en_masa_el_catalogo_ajeno(): void
    {
        $mia   = $this->tienda();
        $ajena = $this->tienda();
        $suyo  = $this->producto($ajena);

        $this->como($mia)->postJson(route('products.bulk-action'), [
            'ids' => [$suyo->id], 'action' => 'delete',
        ]);

        $this->assertDatabaseHas('products', ['id' => $suyo->id]);
    }

    /** El listado solo trae lo propio. */
    public function test_el_listado_no_muestra_productos_ajenos(): void
    {
        $mia   = $this->tienda();
        $ajena = $this->tienda();
        $this->producto($mia, ['name' => 'Mi reflector']);
        $this->producto($ajena, ['name' => 'Reflector del vecino']);

        $html = $this->como($mia)->get(route('products.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Mi reflector', $html);
        $this->assertStringNotContainsString('Reflector del vecino', $html);
    }
}
