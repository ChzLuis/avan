<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Category;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Modules\Tienda\Storefront\CatalogQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un negocio que trabaja por cotización no tiene precios, y su catálogo es un
 * muestrario.
 *
 * La regla general —sin precio no se muestra— existe para no generar pedidos a
 * S/ 0.00, y se queda. Lo que se corrige aquí es que esa regla se aplicaba
 * también a las tiendas por cotización, donde por definición no hay precios:
 * el catálogo salía vacío y la tarjeta "Precio a solicitud" que la plantilla ya
 * traía no se alcanzaba nunca.
 */
class TiendaPorCotizacionTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(string $modo): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Cotiza', 'slug' => 'cot-'.uniqid(), 'is_active' => true,
        ]);
        if ($modo !== '') {
            $project->settings()->create(['key' => 'store_mode', 'value' => $modo]);
        }

        $cat = Category::create([
            'project_id' => $project->id, 'name' => 'Reflectores',
            'slug' => 'reflectores-'.uniqid(), 'is_active' => true,
        ]);

        Product::create([
            'project_id' => $project->id, 'category_id' => $cat->id,
            'name' => 'REFLECTOR SIN PRECIO', 'price' => 0, 'is_available' => true,
        ]);
        Product::create([
            'project_id' => $project->id, 'category_id' => $cat->id,
            'name' => 'REFLECTOR CON PRECIO', 'price' => 120.50, 'is_available' => true,
        ]);

        return $project;
    }

    /** @return array<string> */
    private function mostrables(Project $project): array
    {
        return CatalogQueryService::mostrable($project->products(), $project)
            ->pluck('name')->all();
    }

    /** Tienda normal: sin precio no se muestra, para no vender a S/ 0.00. */
    public function test_la_tienda_normal_oculta_lo_que_no_tiene_precio(): void
    {
        $nombres = $this->mostrables($this->tienda('direct'));

        $this->assertContains('REFLECTOR CON PRECIO', $nombres);
        $this->assertNotContains('REFLECTOR SIN PRECIO', $nombres);
    }

    /** Sin configurar nada se comporta como tienda normal. */
    public function test_por_defecto_se_comporta_como_tienda_normal(): void
    {
        $this->assertNotContains('REFLECTOR SIN PRECIO', $this->mostrables($this->tienda('')));
    }

    /** Por cotización: el catálogo es muestrario y se ve TODO. */
    public function test_por_cotizacion_se_muestran_los_productos_sin_precio(): void
    {
        $nombres = $this->mostrables($this->tienda('quote'));

        $this->assertContains('REFLECTOR SIN PRECIO', $nombres);
        $this->assertContains('REFLECTOR CON PRECIO', $nombres);
    }

    /** Lo que no se relaja nunca: un producto no disponible sigue oculto. */
    public function test_no_disponible_sigue_oculto_aunque_sea_por_cotizacion(): void
    {
        $project = $this->tienda('quote');
        $project->products()->where('name', 'REFLECTOR SIN PRECIO')->update(['is_available' => false]);

        $this->assertNotContains('REFLECTOR SIN PRECIO', $this->mostrables($project));
    }

    /** La decisión tiene un solo dueño, y se puede preguntar. */
    public function test_la_regla_se_consulta_en_un_unico_sitio(): void
    {
        $this->assertTrue(CatalogQueryService::exigePrecio($this->tienda('direct')));
        $this->assertFalse(CatalogQueryService::exigePrecio($this->tienda('quote')));
    }
}
