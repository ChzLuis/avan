<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modulo Control (app/Modules/Control): BIXO Control, el superadmin de Eskala.
 *
 * Se vigila que sus pantallas sigan pintandose con sus vistas bajo
 * `control::` (incluido el layout `<x-admin-layout>`, que ahora vive dentro
 * del modulo y se registra como componente anonimo desde el provider), que la
 * pagina publica de solicitud de demo siga abierta, y que nadie vuelva a
 * crear ni importar estas clases por su ruta vieja.
 */
class ControlModuloTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $admin = User::factory()->create(['is_superadmin' => 1]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_el_tablero_del_superadmin_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->superadmin();

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertViewIs('control::admin.dashboard.index');
    }

    public function test_proyectos_y_licencias_se_pintan_con_la_vista_del_modulo(): void
    {
        $this->superadmin();

        $this->get('/admin/projects')->assertOk()->assertViewIs('control::admin.projects.index');
        $this->get('/admin/licencias')->assertOk()->assertViewIs('control::admin.licenses.index');
    }

    public function test_la_auditoria_usa_el_modelo_movido(): void
    {
        $this->superadmin();

        $this->get('/admin/auditoria')->assertOk()->assertViewIs('control::admin.audit.index');
    }

    /** Un usuario normal sigue sin entrar: el middleware no cambio de sitio. */
    public function test_un_usuario_comun_no_entra_al_superadmin(): void
    {
        $this->actingAs(User::factory()->create(['is_superadmin' => 0]));

        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_la_solicitud_de_demo_sigue_publica(): void
    {
        $this->get('/demo')->assertOk()->assertViewIs('control::demo.index');
    }

    /**
     * Frontera del modulo: estas clases viven SOLO en app/Modules/Control.
     * Otros modulos pueden usarlas (AccessEvent es la auditoria de todos),
     * pero por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/Admin',
            'app/Http/Controllers/DemoController.php',
            'app/Models/AccessEvent.php',
            'app/Models/DemoRequest.php',
            'app/Support/LicenseManager.php',
            'resources/views/admin',
            'resources/views/demo',
            'resources/views/components/admin-layout.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Control es su unico sitio.");
        }

        $patron = '/\bApp\\\\(Models\\\\(AccessEvent|DemoRequest)|Support\\\\LicenseManager|Http\\\\Controllers\\\\(Admin\\\\|DemoController\b))/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Control/') || $rel === 'tests/Feature/ControlModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Control por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
