<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unificación visual del Workspace (Fase 1): las pantallas de configuración
 * estables (Mi negocio, Código QR) se renderizan dentro del MISMO shell que la
 * operación (sidebar comercial unificado), y el sidebar maestro ofrece TODA la
 * configuración real. Las URLs /bixoadmin/* no cambian (compatibilidad).
 */
class WorkspaceShellUnificadoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['is_superadmin' => 0]);
        $this->project = Project::create([
            'owner_id' => $this->owner->id,
            'name' => 'Shell QA', 'slug' => 'shell-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'clients', 'invoices', 'quotes', 'catalog', 'store'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
        // El menú de facturación se libera por uso o por el ajuste del negocio.
        $this->project->settings()->create(['key' => 'modulo_facturas', 'value' => '1']);
        // Permisos explícitos, como un gerente real (el menú filtra por can()).
        $permisos = ['settings.negocio', 'settings.diseno', 'settings.pagos', 'settings.catalogos',
            'catalog.ver', 'invoices.ver', 'orders.ver', 'view-orders', 'reports.ver', 'clients.ver'];
        foreach ($permisos as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $rol = \Spatie\Permission\Models\Role::findOrCreate('shell_qa', 'web');
        $rol->syncPermissions($permisos);
        $this->owner->syncRoles(['shell_qa']);
        // El dueño entra con la sesión unificada (F3): ambas claves.
        $this->actingAs($this->owner)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
    }

    public function test_mi_negocio_se_renderiza_en_el_shell_unificado(): void
    {
        $res = $this->get('/bixoadmin/settings');

        $res->assertOk()
            // Marca del shell comercial unificado (nav-marca del _sidebar):
            ->assertSee('nav-marca-texto', false)
            // Y ya no el sidebar del panel viejo:
            ->assertDontSee('admin-sidebar', false);
    }

    public function test_codigo_qr_se_renderiza_en_el_shell_unificado(): void
    {
        $res = $this->get('/bixoadmin/settings/qr');

        $res->assertOk()
            ->assertSee('nav-marca-texto', false)
            ->assertDontSee('admin-sidebar', false);
    }

    public function test_el_sidebar_maestro_ofrece_toda_la_configuracion(): void
    {
        // Dos caras (plan 2026-08-30): el árbol COMPLETO de configuración se ve
        // en la cara Configuración; la Operación ofrece solo la puerta.
        $res = $this->get('/bixoadmin/settings');

        $res->assertOk()
            ->assertSee('Datos del negocio')
            ->assertSee('SEO')
            ->assertSee('Pagos')
            ->assertSee('Módulos')
            ->assertSee('Roles y permisos')
            ->assertSee('Constructor (tienda)')
            ->assertSee('Ir a Ventas');

        // Y la operación mantiene lo suyo (sin mezclar configuración).
        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Guías de remisión')
            ->assertSee('Ir a Configuración')
            ->assertDontSee('Constructor (tienda)');
    }

    public function test_las_urls_del_panel_no_cambian(): void
    {
        // Compatibilidad: mismas rutas, solo cambió el shell que las viste.
        $this->get('/bixoadmin/settings')->assertOk();
        $this->get('/bixoadmin/settings/qr')->assertOk();
    }
}
