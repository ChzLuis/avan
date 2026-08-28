<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cierre del bypass de F5: las rutas del portal legado `/f/{slug}` exigen los
 * MISMOS permisos por verbo que su gemelo en /bixosales. Antes bastaba con
 * tener la sesión `facturacion_auth.{slug}` (ser miembro) para operar sin
 * ningún permiso: crear/borrar clientes, vender, emitir y anular comprobantes.
 */
class PortalFacturacionRbacTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Portal RBAC QA', 'slug' => 'portal-rbac-qa', 'category' => 'retail', 'is_active' => true,
        ]);
    }

    /** Autentica un miembro con el rol dado (o sin permisos) en el portal f/{slug}. */
    private function comoMiembro(array $permisos): User
    {
        $rol = Role::findOrCreate('portal_rbac_'.md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            "facturacion_auth.{$this->project->slug}" => true,
            // El login de facturación fija el proyecto en sesión; sin él, el
            // route-model-binding con scope daría 404 antes que 403.
            'active_project_id' => $this->project->id,
        ]);
        return $u;
    }

    public function test_un_miembro_sin_permiso_ya_no_crea_clientes_por_el_portal_legado(): void
    {
        // Miembro logueado en el portal PERO sin clients.crear: antes pasaba.
        $this->comoMiembro(['clients.ver']);

        $this->post("/f/{$this->project->slug}/clientes", ['name' => 'Intruso', 'phone' => '999'])
            ->assertForbidden();

        $this->assertDatabaseMissing('clients', ['name' => 'Intruso']);
    }

    public function test_un_miembro_sin_permiso_no_borra_clientes_por_el_portal_legado(): void
    {
        $this->comoMiembro(['clients.ver']);
        $cliente = Client::create(['project_id' => $this->project->id, 'name' => 'Cliente Real', 'phone' => '111']);

        $this->delete("/f/{$this->project->slug}/clientes/{$cliente->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('clients', ['id' => $cliente->id]);
    }

    public function test_con_el_permiso_correcto_si_opera_por_el_portal_legado(): void
    {
        // Un rol con manage-clients (legacy) también debe pasar por el pipe dual.
        Permission::findOrCreate('manage-clients', 'web');
        $this->comoMiembro(['clients.ver', 'manage-clients']);

        $this->post("/f/{$this->project->slug}/clientes", ['name' => 'Cliente OK', 'phone' => '222'])
            ->assertSuccessful();

        $this->assertDatabaseHas('clients', ['name' => 'Cliente OK', 'project_id' => $this->project->id]);
    }
}
