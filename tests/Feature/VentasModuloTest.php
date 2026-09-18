<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Modulo Ventas (app/Modules/Ventas): pedidos, cotizaciones, propuestas, POS,
 * portal del cliente, revendedores, reportes comerciales, tablero comercial,
 * WooSync y tickets. `Cobranza` paso a Finanzas en el mismo paso.
 *
 * Aqui NO se prueba la venta (la cubren sus ~50 tests propios); se vigila que
 * cada pantalla siga pintandose con su vista bajo `ventas::` y que nadie
 * vuelva a crear ni importar estas clases por su ruta vieja. Los nombres de
 * ruta `orders.*`, `quotes.*`, `pos.*`... NO son vistas.
 */
class VentasModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['orders.ver', 'quotes.ver', 'pos.usar', 'reports.ver', 'catalog.ver'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create(['is_superadmin' => true])->id,
            'name'      => 'Negocio ventas',
            'slug'      => 'negocio-ventas-' . uniqid(),
            'is_active' => true,
        ]);
        foreach (['orders', 'quotes', 'catalog', 'invoices'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        Role::findOrCreate('ventas_test', 'web')->syncPermissions(['orders.ver', 'quotes.ver', 'pos.usar', 'reports.ver', 'catalog.ver']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Vendedora', 'spatie_role' => 'ventas_test', 'is_active' => 1,
        ]);
        $user->syncRoles(['ventas_test']);

        $this->actingAs($user)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    public function test_pedidos_y_cotizaciones_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->get('/bixosales/pedidos')->assertOk()->assertViewIs('ventas::orders.index');
        $this->get('/bixosales/cotizaciones')->assertOk()->assertViewIs('ventas::quotes.index');
    }

    public function test_pos_y_reportes_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->get('/bixosales/pos')->assertOk()->assertViewIs('ventas::pos.index');
        $this->get('/bixosales/reportes/ventas')->assertOk()->assertViewIs('ventas::comercial.reportes.ventas');
    }

    public function test_propuestas_y_tablero_comercial_del_panel_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->actingAs($this->project->owner)->withSession(['active_project_id' => $this->project->id]);

        $this->get('/bixoadmin/proposals')->assertOk()->assertViewIs('ventas::proposals.index');
        $this->get('/bixoadmin/dashboard-comercial')->assertOk()->assertViewIs('ventas::dashboard.comercial');
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Ventas (y Cobranza en Finanzas). Quien las necesite las
     * importa por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/OrderController.php',
            'app/Http/Controllers/QuoteController.php',
            'app/Http/Controllers/PosController.php',
            'app/Http/Controllers/ProposalController.php',
            'app/Http/Controllers/PortalController.php',
            'app/Http/Controllers/PortalClienteController.php',
            'app/Http/Controllers/ResellerController.php',
            'app/Http/Controllers/WooSyncController.php',
            'app/Http/Controllers/ReporteController.php',
            'app/Http/Controllers/DashboardComercialController.php',
            'app/Http/Controllers/TicketsWpController.php',
            'app/Models/Order.php',
            'app/Models/OrderItem.php',
            'app/Models/OrderEvent.php',
            'app/Models/Quote.php',
            'app/Models/QuoteItem.php',
            'app/Models/Proposal.php',
            'app/Models/ResellerPrice.php',
            'app/Models/SalesInteraction.php',
            'app/Models/AbandonedCart.php',
            'app/Support/OrderFlow.php',
            'app/Support/OrderStatus.php',
            'app/Support/OrderAbilities.php',
            'app/Support/QuoteAbilities.php',
            'app/Support/QuoteStatus.php',
            'app/Support/Cobranza.php',
            'resources/views/orders',
            'resources/views/quotes',
            'resources/views/pos',
            'resources/views/proposals',
            'resources/views/reseller',
            'resources/views/portal-cliente',
            'resources/views/ventas',
            'resources/views/dashboard/comercial.blade.php',
            'resources/views/comercial/reportes',
            'resources/views/comercial/woo-orders.blade.php',
            'resources/views/comercial/tickets-wp.blade.php',
            'resources/views/facturacion/pedidos',
            'resources/views/facturacion/cotizaciones',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Ventas es su unico sitio.");
        }

        $patron = '/\\bApp\\\\('
            . 'Models\\\\(Order|OrderItem|OrderEvent|Quote|QuoteItem|Proposal|ResellerPrice|SalesInteraction|AbandonedCart)\\b'
            . '|Support\\\\(OrderFlow|OrderStatus|OrderAbilities|QuoteAbilities|QuoteStatus|Cobranza)\\b'
            . '|Http\\\\Controllers\\\\(Order|Quote|Pos|Proposal|Portal|PortalCliente|Reseller|WooSync|Reporte|DashboardComercial|TicketsWp)Controller\\b'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Ventas/') || $rel === 'tests/Feature/VentasModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Ventas por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
