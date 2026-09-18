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
 * Modulo Finanzas (app/Modules/Finanzas): comprobantes, SUNAT, guias, caja,
 * cobranza, certificados, lector de comprobantes.
 *
 * Aqui NO se prueba la logica fiscal (la cubren 40 tests propios); se vigila
 * que las pantallas sigan pintandose con sus vistas bajo `finanzas::` tras la
 * mudanza —incluidas las que eligen plantilla de PDF por ternario, que el
 * generador no veia— y que nadie vuelva a crear ni importar estas clases por
 * su ruta vieja.
 */
class FinanzasModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['invoices.ver', 'invoices.crear', 'reports.ver', 'caja.ver', 'catalog.ver'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio finanzas',
            'slug'      => 'negocio-finanzas',
            'is_active' => true,
        ]);
        foreach (['invoices', 'orders', 'catalog', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        Role::findOrCreate('finanzas_test', 'web')->syncPermissions(['invoices.ver', 'invoices.crear', 'reports.ver', 'caja.ver', 'catalog.ver']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Contadora', 'spatie_role' => 'finanzas_test', 'is_active' => 1,
        ]);
        $user->syncRoles(['finanzas_test']);

        $this->actingAs($user)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    /**
     * La portada fiscal es la pantalla de telefono: en escritorio redirige a
     * la lista de comprobantes (regla previa a la mudanza, no de este modulo).
     */
    public function test_la_portada_fiscal_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixosales/facturacion?movil=1')
            ->assertOk()
            ->assertViewIs('finanzas::facturacion.portada');

        $this->get('/bixosales/facturacion')
            ->assertRedirect(route('bixosales.facturas'));
    }

    public function test_cuentas_por_cobrar_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixosales/cuentas')
            ->assertOk()
            ->assertViewIs('finanzas::cxc.index');
    }

    public function test_certificados_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixoadmin/certificados')
            ->assertOk()
            ->assertViewIs('finanzas::certificados.index');
    }

    /** Facturas elige la vista segun el portal: basta con que sea del modulo. */
    public function test_facturas_se_pinta_con_una_vista_del_modulo(): void
    {
        $res = $this->get('/bixosales/facturas')->assertOk();

        $this->assertStringStartsWith('finanzas::', $res->original->name(), 'La lista de comprobantes no sale de una vista del modulo Finanzas.');
    }

    /** La caja sigue en su vista del portal comercial: el controlador se movio, la vista no. */
    public function test_la_caja_sigue_abriendo(): void
    {
        $this->get('/bixosales/caja')->assertOk()->assertViewIs('comercial.caja');
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Finanzas. Otros modulos las usan (Ledger desde POS y pedidos,
     * Invoice desde clientes), pero por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/Facturacion',
            'app/Http/Controllers/InvoiceController.php',
            'app/Http/Controllers/PaymentController.php',
            'app/Http/Controllers/CajaController.php',
            'app/Http/Controllers/CxcController.php',
            'app/Http/Controllers/CertificadoController.php',
            'app/Http/Controllers/LectorComprobanteController.php',
            'app/Models/Invoice.php', 'app/Models/InvoiceItem.php', 'app/Models/Payment.php',
            'app/Models/ReceivableTerm.php', 'app/Models/Caja.php', 'app/Models/CajaMovimiento.php',
            'app/Models/Certificado.php', 'app/Models/LecturaComprobante.php',
            'app/Models/GuiaRemision.php', 'app/Models/GuiaRemisionItem.php',
            'app/Support/Sunat', 'app/Support/Lector', 'app/Support/ApisPeruService.php',
            'app/Support/NubefactService.php', 'app/Support/CatalogoDocumentos.php', 'app/Support/Ledger.php',
            'app/Jobs/SendInvoiceToSunat.php', 'app/Jobs/DarDeBajaEnSunat.php', 'app/Jobs/EnviarGuiaASunat.php',
            'app/Console/Commands/ArchivarComprobantes.php', 'app/Console/Commands/ReintentarComprobantes.php',
            'resources/views/invoices', 'resources/views/cxc', 'resources/views/certificados',
            'resources/views/facturacion/facturas', 'resources/views/facturacion/guias',
            'resources/views/facturacion/layouts', 'resources/views/facturacion/portada.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Finanzas es su unico sitio.");
        }

        $patron = '/\bApp\\\\('
            . 'Models\\\\(Invoice|InvoiceItem|Payment|ReceivableTerm|Caja|CajaMovimiento|Certificado|LecturaComprobante|GuiaRemision|GuiaRemisionItem)\b'
            . '|Support\\\\(Sunat\\\\|Lector\\\\|ApisPeruService\b|NubefactService\b|CatalogoDocumentos\b|Ledger\b)'
            . '|Jobs\\\\(SendInvoiceToSunat|DarDeBajaEnSunat|EnviarGuiaASunat)\b'
            . '|Console\\\\Commands\\\\(ArchivarComprobantes|ReintentarComprobantes)\b'
            . '|Http\\\\Controllers\\\\(Facturacion\\\\|(Invoice|Payment|Caja|Cxc|Certificado|LectorComprobante)Controller\b)'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Finanzas/') || $rel === 'tests/Feature/FinanzasModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Finanzas por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
