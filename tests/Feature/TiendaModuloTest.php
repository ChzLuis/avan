<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modulo Tienda (app/Modules/Tienda): la tienda publica (catalogo, producto,
 * checkout, paginas institucionales, libro de reclamaciones), el Constructor,
 * plantillas de diseño, menus, promociones, cupones, reseñas y todo el
 * soporte del storefront (Storefront/, Storefront* en Support/).
 *
 * Aqui NO se prueba la tienda (la cubren ~50 tests propios); se vigila que
 * cada pantalla siga pintandose con su vista bajo `tienda::` tras la mudanza
 * —incluidas las que eligen plantilla por arreglo o concatenacion, que un
 * `view()->exists()` convertiria en silencio en "sin cabecera"— y que nadie
 * vuelva a crear ni importar estas clases por su ruta vieja.
 */
class TiendaModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Project::create([
            'owner_id'  => User::factory()->create(['is_superadmin' => true])->id,
            'name'      => 'Tienda modulo',
            'slug'      => 'tienda-modulo-' . uniqid(),
            'is_active' => true,
        ]);
    }

    private function enElPanel()
    {
        return $this->actingAs($this->proyecto->owner)
            ->withSession(['active_project_id' => $this->proyecto->id]);
    }

    /** La portada publica elige plantilla por arreglo: basta con que sea del modulo. */
    public function test_la_tienda_publica_se_pinta_con_una_vista_del_modulo(): void
    {
        $res = $this->get(route('public.catalog', $this->proyecto->slug))->assertOk();

        $this->assertInstanceOf(\Illuminate\View\View::class, $res->original);
        $this->assertStringStartsWith('tienda::', $res->original->name(), 'La tienda publica no sale de una vista del modulo Tienda.');
    }

    public function test_el_constructor_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->enElPanel()->get('/bixoadmin/settings/builder')
            ->assertOk()
            ->assertViewIs('tienda::settings.builder.index');
    }

    public function test_promociones_reclamaciones_y_plantillas_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->enElPanel()->get(route('promotions.index'))
            ->assertOk()
            ->assertViewIs('tienda::promotions.index');

        $this->enElPanel()->get(route('complaints.index'))
            ->assertOk()
            ->assertViewIs('tienda::complaints.index');

        $this->enElPanel()->get('/bixoadmin/settings/design-templates')
            ->assertOk()
            ->assertViewIs('tienda::settings.design-templates');
    }

    /**
     * Los parciales de cabecera/pie/carrito se resuelven por nombre construido
     * (StorefrontLayoutPacks, HeaderPresets): si el prefijo faltara, exists()
     * devolveria false y la tienda saldria sin cabecera, sin error.
     */
    public function test_los_parciales_construidos_por_nombre_existen_bajo_el_modulo(): void
    {
        foreach (['headers.classic', 'footers.classic', 'carts.classic'] as $parcial) {
            $this->assertTrue(view()->exists('tienda::storefront.partials.' . $parcial), "Falta tienda::storefront.partials.{$parcial}");
        }
        $this->assertTrue(view()->exists('tienda::layouts.storefront'));
        $this->assertTrue(view()->exists('tienda::components.storefront.header'));
    }

    public function test_los_comandos_del_modulo_siguen_registrados(): void
    {
        $comandos = array_keys(\Illuminate\Support\Facades\Artisan::all());

        foreach (['bixo:auditar-constructor', 'storefront:consolidate-engines', 'productos:fusionar-modelos'] as $c) {
            $this->assertContains($c, $comandos);
        }
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Tienda. Quien las necesite las importa por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/PublicController.php',
            'app/Http/Controllers/StoreBuilderController.php',
            'app/Http/Controllers/StoreExperienceController.php',
            'app/Http/Controllers/StoreNavigationController.php',
            'app/Http/Controllers/StorePageController.php',
            'app/Http/Controllers/DesignTemplateController.php',
            'app/Http/Controllers/PromotionController.php',
            'app/Http/Controllers/ComplaintController.php',
            'app/Http/Controllers/CatalogProfileController.php',
            'app/Models/Coupon.php',
            'app/Models/Promotion.php',
            'app/Models/Review.php',
            'app/Models/StoreCatalogProfile.php',
            'app/Models/StoreMenu.php',
            'app/Models/StoreMenuItem.php',
            'app/Models/StorePage.php',
            'app/Models/StorePopup.php',
            'app/Models/StoreSection.php',
            'app/Models/DesignTemplate.php',
            'app/Models/DesignTemplateVersion.php',
            'app/Models/ProjectTemplate.php',
            'app/Models/ContactMessage.php',
            'app/Models/Complaint.php',
            'app/Support/StorefrontLayoutPacks.php',
            'app/Support/StorefrontNavigation.php',
            'app/Support/StorefrontSections.php',
            'app/Support/StorefrontTheme.php',
            'app/Support/StorefrontThemePresets.php',
            'app/Support/HeaderPresets.php',
            'app/Support/DesignerIcons.php',
            'app/Support/ContenidoEjemplo.php',
            'app/Support/CatalogTemplates.php',
            'app/Support/CategoryIcons.php',
            'app/Support/IconosCategoria.php',
            'app/Storefront',
            'app/Console/Commands/AuditarConstructor.php',
            'app/Console/Commands/ConsolidarMotoresTienda.php',
            'app/Console/Commands/FusionarModelos.php',
            'resources/views/public',
            'resources/views/storefront',
            'resources/views/promotions',
            'resources/views/complaints',
            'resources/views/settings/builder',
            'resources/views/settings/design-templates.blade.php',
            'resources/views/layouts/storefront.blade.php',
            'resources/views/components/storefront',
            'resources/views/components/computienda',
            'resources/views/components/store-menu.blade.php',
            'resources/views/components/public-popup.blade.php',
            'resources/views/components/public-store-runtime.blade.php',
            'resources/views/components/storefront-home-sections.blade.php',
            'resources/views/components/storefront-home-skins.blade.php',
            'resources/views/components/storefront-motion.blade.php',
            'resources/views/components/storefront-shapes.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Tienda es su unico sitio.");
        }

        $patron = '/\\bApp\\\\('
            . 'Models\\\\(Coupon|Promotion|Review|StoreCatalogProfile|StoreMenu|StoreMenuItem|StorePage|StorePopup|StoreSection|DesignTemplate|DesignTemplateVersion|ProjectTemplate|ContactMessage|Complaint)\\b'
            . '|Support\\\\(StorefrontLayoutPacks|StorefrontNavigation|StorefrontSections|StorefrontTheme|StorefrontThemePresets|HeaderPresets|DesignerIcons|ContenidoEjemplo|CatalogTemplates|CategoryIcons|IconosCategoria)\\b'
            . '|Storefront\\\\'
            . '|Console\\\\Commands\\\\(AuditarConstructor|ConsolidarMotoresTienda|FusionarModelos)\\b'
            . '|Http\\\\Controllers\\\\(Public|StoreBuilder|StoreExperience|StoreNavigation|StorePage|DesignTemplate|Promotion|Complaint|CatalogProfile)Controller\\b'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Tienda/') || $rel === 'tests/Feature/TiendaModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Tienda por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
