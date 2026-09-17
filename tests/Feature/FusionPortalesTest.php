<?php

namespace Tests\Feature;

use App\Modules\Control\Models\AccessEvent;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fases 3-5 de la reestructuración: de siete puertas a dos.
 *
 * F3a — sesión única: el login comercial deja las DOS claves alineadas.
 * F3b — impersonación auditada: soporte entra al Workspace dejando rastro.
 * F4  — App Shell: el menú de sales absorbe la configuración, por permisos.
 * F5  — /f/{slug} se retira: sus entradas redirigen al portal único.
 */
class FusionPortalesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $gerente;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'view-orders', 'settings.negocio', 'settings.diseno', 'catalog.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Fusión QA', 'slug' => 'fusion-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        Role::findOrCreate('fusion_gerente', 'web')->syncPermissions(['orders.ver', 'settings.negocio', 'settings.diseno', 'catalog.ver']);
        $this->gerente = User::factory()->create(['is_superadmin' => 0, 'password' => Hash::make('clave-qa-123')]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->gerente->id, 'role' => 'admin']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $this->gerente->id,
            'name' => 'Gerente Fusión', 'spatie_role' => 'fusion_gerente', 'is_active' => 1]);
        $this->gerente->syncRoles(['fusion_gerente']);
    }

    /** F3a: entrar por el portal comercial deja las dos claves de sesión
     *  alineadas — nadie vuelve a loguearse dos veces para ver el panel. */
    public function test_el_login_comercial_deja_la_sesion_unificada(): void
    {
        $res = $this->post('/bixosales/login', [
            'email' => $this->gerente->email,
            'password' => 'clave-qa-123',
            'project_id' => $this->project->id,
        ]);

        $res->assertRedirect();
        $this->assertSame($this->project->id, session('comercial_project_id'));
        $this->assertSame($this->project->id, session('active_project_id'),
            'La clave del panel debe quedar alineada con la comercial');
    }

    /** F5: la puerta del portal de facturación redirige al portal único. */
    public function test_el_portal_de_facturacion_redirige_al_portal_unico(): void
    {
        $this->get('/f/fusion-qa/login')->assertRedirect(route('bixosales.login'));
    }

    /** F3b: la impersonación exige superadmin y deja rastro auditado. */
    public function test_la_impersonacion_es_solo_de_superadmin_y_deja_rastro(): void
    {
        // Un gerente común no puede.
        $this->actingAs($this->gerente)
            ->post('/bixoadmin/entrar-como/'.$this->project->id)
            ->assertForbidden();
        $this->assertSame(0, AccessEvent::count());

        // El superadmin sí, y queda registrado con IP y actor.
        $admin = User::factory()->create(['is_superadmin' => 1]);
        $this->actingAs($admin)
            ->post('/bixoadmin/entrar-como/'.$this->project->id)
            ->assertRedirect('/bixosales');

        $this->assertSame($this->project->id, session('active_project_id'));
        $evento = AccessEvent::first();
        $this->assertNotNull($evento, 'La impersonación sin rastro no existe');
        $this->assertSame('impersonate', $evento->action);
        $this->assertSame($admin->id, $evento->actor_id);
        $this->assertSame($this->project->id, $evento->project_id);
    }

    /** La salida deja el mismo rastro que la entrada: la auditoría sabe cuánto duró. */
    public function test_salir_de_la_impersonacion_limpia_sesion_y_deja_rastro(): void
    {
        $admin = User::factory()->create(['is_superadmin' => 1]);
        $this->actingAs($admin)->post('/bixoadmin/entrar-como/'.$this->project->id);

        $this->post('/bixoadmin/salir-de-impersonacion')
            ->assertRedirect(route('workspace'));

        $this->assertNull(session('active_project_id'));
        $this->assertNull(session('comercial_project_id'));
        $fin = AccessEvent::where('action', 'impersonate_end')->first();
        $this->assertNotNull($fin, 'La salida sin rastro deja la auditoría a medias');
        $this->assertSame($admin->id, $fin->actor_id);
        $this->assertSame($this->project->id, $fin->project_id);

        // Y un gerente común no puede invocarla.
        $this->actingAs($this->gerente)
            ->post('/bixoadmin/salir-de-impersonacion')
            ->assertForbidden();
    }

    /** F4 (dos caras, plan 2026-08-30): la Operación ofrece la PUERTA a
     *  Configuración a quien tiene permisos de ajustes — no mezcla sus
     *  entradas — y a quien no los tiene ni le enseña la puerta. */
    public function test_el_menu_unico_respeta_los_permisos(): void
    {
        // Gerente con permisos de ajustes: en Operación ve la puerta, y en
        // Configuración ve el árbol completo de su cara.
        $this->actingAs($this->gerente)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Ir a Configuración')
            // La operación no mezcla entradas de configuración: solo la puerta.
            ->assertDontSee('Certificados SUNAT');

        // Y la configuración se sirve con SU shell (el del panel), no con el
        // de ventas: cada cara conserva su diseño.
        $this->get('/bixoadmin/settings')
            ->assertOk()
            ->assertSee('id="admin-sidebar"', false)
            ->assertSee('Certificados SUNAT');

        // Vendedor sin permisos de ajustes: ni la puerta aparece.
        Role::findOrCreate('fusion_vendedor', 'web')->syncPermissions(['orders.ver']);
        $vendedor = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $vendedor->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $vendedor->id,
            'name' => 'Vendedor', 'spatie_role' => 'fusion_vendedor', 'is_active' => 1]);
        $vendedor->syncRoles(['fusion_vendedor']);

        $this->actingAs($vendedor)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        $this->get('/bixosales')
            ->assertOk()
            ->assertDontSee('Ir a Configuración')
            ->assertDontSee('Constructor (tienda)');
    }
}
