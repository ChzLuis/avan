<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Modules\Catalogo\Conectores\Registry\CatalogProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modulo Catalogo (app/Modules/Catalogo): productos, categorias, servicios,
 * variantes y atributos, listas de catalogo, combos, plantillas de imagen,
 * importacion y los conectores de catalogo (SISKOTE; antes app/Catalog).
 *
 * Aqui NO se prueba el catalogo (lo cubren sus ~50 tests propios); se vigila
 * que cada pantalla siga pintandose con su vista bajo `catalogo::`, que los
 * comandos y el provider de conectores sigan registrados, y que nadie vuelva
 * a crear ni importar estas clases por su ruta vieja. Ojo: `catalog.*` es
 * ademas nombre de PERMISO (catalog.ver, catalog.editar): no son vistas.
 */
class CatalogoModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Project::create([
            'owner_id'  => User::factory()->create(['is_superadmin' => true])->id,
            'name'      => 'Catalogo modulo',
            'slug'      => 'catalogo-modulo-' . uniqid(),
            'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'catalog', 'is_active' => true]);
        $this->proyecto->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function enElPanel()
    {
        return $this->actingAs($this->proyecto->owner)
            ->withSession(['active_project_id' => $this->proyecto->id]);
    }

    public function test_productos_categorias_y_servicios_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->enElPanel()->get('/bixoadmin/products')->assertOk()->assertViewIs('catalogo::catalog.products.index');
        $this->enElPanel()->get('/bixoadmin/categories')->assertOk()->assertViewIs('catalogo::catalog.categories.index');
        $this->enElPanel()->get('/bixoadmin/services')->assertOk()->assertViewIs('catalogo::catalog.services.index');
    }

    public function test_listas_combos_e_integraciones_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->enElPanel()->get('/bixoadmin/catalogs')->assertOk()->assertViewIs('catalogo::catalogs.index');
        $this->enElPanel()->get('/bixoadmin/combos')->assertOk()->assertViewIs('catalogo::combos.index');
        $this->enElPanel()->get('/bixoadmin/catalog-integrations')->assertOk()->assertViewIs('catalogo::catalog.integrations.index');
    }

    /** El provider del modulo registra los conectores (SISKOTE y el falso de pruebas). */
    public function test_el_registro_de_conectores_sigue_cargado(): void
    {
        $registro = app(CatalogProviderRegistry::class);

        $this->assertTrue($registro->has('siskote'), 'El conector SISKOTE ya no esta registrado.');
    }

    public function test_los_comandos_del_modulo_siguen_registrados(): void
    {
        $comandos = array_keys(\Illuminate\Support\Facades\Artisan::all());

        foreach (['catalog:sync', 'bixo:generar-sku', 'imagenes:importar-externas', 'imagenes:regenerar'] as $c) {
            $this->assertContains($c, $comandos);
        }
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Catalogo. Quien las necesite las importa por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/Catalog',
            'app/Http/Controllers/CatalogListController.php',
            'app/Http/Controllers/ComboController.php',
            'app/Http/Controllers/ProductImageTemplateController.php',
            'app/Models/Product.php',
            'app/Models/ProductAttribute.php',
            'app/Models/ProductAttributeValue.php',
            'app/Models/ProductImage.php',
            'app/Models/ProductImageTemplate.php',
            'app/Models/ProductVariant.php',
            'app/Models/Category.php',
            'app/Models/Service.php',
            'app/Models/CatalogList.php',
            'app/Models/CatalogValue.php',
            'app/Models/CatalogIntegration.php',
            'app/Models/CatalogIntegrationItem.php',
            'app/Models/CatalogSyncRun.php',
            'app/Models/ImportLog.php',
            'app/Models/Combo.php',
            'app/Models/ComboItem.php',
            'app/Support/EtiquetasProducto.php',
            'app/Support/UnidadesMedida.php',
            'app/Support/SquareImage.php',
            'app/Catalog',
            'app/Providers/CatalogServiceProvider.php',
            'app/Console/Commands/SyncCatalogCommand.php',
            'app/Console/Commands/GenerarSkuProductos.php',
            'app/Console/Commands/ImportarImagenesExternas.php',
            'app/Console/Commands/RegenerarImagenes.php',
            'app/Jobs/GenerarImagenesProducto.php',
            'app/Jobs/RunCatalogSync.php',
            'resources/views/catalog',
            'resources/views/catalogs',
            'resources/views/combos',
            'resources/views/components/etiquetas-producto.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Catalogo es su unico sitio.");
        }

        $patron = '/\\bApp\\\\('
            . 'Models\\\\(Product|ProductAttribute|ProductAttributeValue|ProductImage|ProductImageTemplate|ProductVariant|Category|Service|CatalogList|CatalogValue|CatalogIntegration|CatalogIntegrationItem|CatalogSyncRun|ImportLog|Combo|ComboItem)\\b'
            . '|Support\\\\(EtiquetasProducto|UnidadesMedida|SquareImage)\\b'
            . '|Catalog\\\\'
            . '|Providers\\\\CatalogServiceProvider\\b'
            . '|Jobs\\\\(GenerarImagenesProducto|RunCatalogSync)\\b'
            . '|Console\\\\Commands\\\\(SyncCatalogCommand|GenerarSkuProductos|ImportarImagenesExternas|RegenerarImagenes)\\b'
            . '|Http\\\\Controllers\\\\(Catalog\\\\|(CatalogList|Combo|ProductImageTemplate)Controller\\b)'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Catalogo/') || $rel === 'tests/Feature/CatalogoModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Catalogo por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
