<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UN SOLO MENÚ DENTRO DE CADA CARA (queja del usuario, 2026-08-30).
 *
 * El problema era que al navegar por /bixoadmin el menú CAMBIABA: unas
 * pantallas se servían con el shell del panel y otras con el comercial.
 *
 * La solución NO es mezclar diseños —un intento previo llevó el encabezado y
 * las alertas de ventas al panel y le quitó el selector de negocio, y el
 * usuario lo rechazó— sino que cada cara use SIEMPRE el suyo:
 *   /bixoadmin (Configuración) → shell del panel (#admin-sidebar)
 *   /bixosales (Operación)     → shell comercial (nav-marca)
 */
class ShellUnicoWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $duenio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->duenio = User::factory()->create(['is_superadmin' => 0]);
        $this->project = Project::create([
            'owner_id' => $this->duenio->id,
            'name' => 'Shell Unico QA', 'slug' => 'shell-unico-qa',
            'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'clients', 'invoices', 'quotes', 'catalog', 'store', 'bots'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->duenio->id, 'role' => 'owner']);

        $permisos = ['settings.negocio', 'settings.diseno', 'settings.pagos', 'settings.catalogos',
            'catalog.ver', 'invoices.ver', 'orders.ver', 'view-orders', 'reports.ver', 'clients.ver', 'hr.ver'];
        foreach ($permisos as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('shell_unico_qa', 'web')->syncPermissions($permisos);
        $this->duenio->syncRoles(['shell_unico_qa']);

        $this->actingAs($this->duenio)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
    }

    public static function pantallasDeConfiguracion(): array
    {
        return [
            'Datos del negocio'  => ['/bixoadmin/settings'],
            'Código QR'          => ['/bixoadmin/settings/qr'],
            'Sedes'              => ['/bixoadmin/company/sedes'],
            'Proveedores'        => ['/bixoadmin/company/proveedores'],
            'Grupos'             => ['/bixoadmin/company/groups'],
            'Productos'          => ['/bixoadmin/products'],
            'Certificados SUNAT' => ['/bixoadmin/certificados'],
            'Canales WhatsApp'   => ['/bixoadmin/bots'],
        ];
    }

    /**
     * @dataProvider pantallasDeConfiguracion
     */
    public function test_toda_la_configuracion_usa_el_mismo_menu(string $ruta): void
    {
        $html = $this->get($ruta)->assertOk()->getContent();

        $this->assertStringContainsString('id="admin-sidebar"', $html,
            "{$ruta} no se sirve con el shell del panel: el usuario cambia de menú al llegar aquí.");
        $this->assertSame(1, substr_count($html, 'id="admin-sidebar"'),
            "{$ruta} pinta más de un menú.");
        $this->assertSame(0, substr_count($html, 'nav-marca-texto'),
            "{$ruta} trae el shell de ventas a la cara de configuración.");
    }

    /** La operación conserva el suyo: son dos diseños, no uno. */
    public function test_la_operacion_conserva_su_propio_shell(): void
    {
        $html = $this->get('/bixosales')->assertOk()->getContent();

        $this->assertStringContainsString('nav-marca-texto', $html);
        $this->assertSame(0, substr_count($html, 'id="admin-sidebar"'));
    }

    /** El panel ofrece el selector de negocio: sin él no se puede cambiar de proyecto. */
    public function test_el_panel_conserva_el_selector_de_negocio(): void
    {
        $html = $this->get('/bixoadmin/settings')->assertOk()->getContent();

        $this->assertStringContainsString('bx-hdr-project-btn', $html,
            'Sin el selector del encabezado no hay forma de cambiar de negocio.');
        $this->assertStringContainsString(route('workspace.select', $this->project), $html);
    }

    /** Certificados SUNAT existía pero no estaba en NINGÚN menú: solo por URL. */
    public function test_el_menu_ofrece_certificados_sunat(): void
    {
        $this->get('/bixoadmin/settings')->assertOk()->assertSee('Certificados SUNAT');
    }
}
