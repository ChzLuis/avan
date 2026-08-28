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
 * Entitlement efectivo en /bixosales (RISK-013): ACCESS = TENANT_ENTITLEMENT
 * AND USER_PERMISSION. Una empresa que NO contrató el módulo no entra por URL
 * aunque el usuario tenga el permiso; ocultar el menú no basta.
 */
class ComercialEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function proyectoConModulos(array $moduleKeys): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ent QA', 'slug' => 'ent-qa-'.uniqid(), 'category' => 'retail', 'is_active' => true,
        ]);
        foreach ($moduleKeys as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        return $project;
    }

    /** Usuario con el permiso dado, en el proyecto dado, autenticado en bixosales. */
    private function usuarioCon(Project $project, array $permisos): void
    {
        foreach ($permisos as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $rol = Role::findOrCreate('ent_qa_'.$project->id, 'web');
        $rol->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $u->id, 'role' => 'editor']);
        Employee::create(['project_id' => $project->id, 'user_id' => $u->id,
            'name' => 'QA', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $project->id, 'active_project_id' => $project->id,
        ]);
    }

    public function test_empresa_sin_el_modulo_no_entra_aunque_tenga_permiso(): void
    {
        // Contrató clientes pero NO facturas; el usuario sí tiene invoices.ver.
        $project = $this->proyectoConModulos(['clients']);
        $this->usuarioCon($project, ['invoices.ver', 'clients.ver']);

        $this->get('/bixosales/facturas')->assertForbidden();
    }

    public function test_empresa_con_modulo_pero_usuario_sin_permiso_es_denegado(): void
    {
        // Sí contrató facturas, pero el usuario no tiene invoices.ver.
        $project = $this->proyectoConModulos(['invoices']);
        $this->usuarioCon($project, ['clients.ver']); // permiso de otra cosa

        $this->get('/bixosales/facturas')->assertForbidden();
    }

    public function test_empresa_con_modulo_y_permiso_si_entra(): void
    {
        $project = $this->proyectoConModulos(['invoices']);
        $this->usuarioCon($project, ['invoices.ver']);

        $this->get('/bixosales/facturas')->assertOk();
    }

    public function test_el_gate_no_corta_capacidades_sin_modulo_mapeado(): void
    {
        // El dashboard/inicio no está mapeado a un módulo: debe pasar con permiso.
        $project = $this->proyectoConModulos(['orders']);
        $this->usuarioCon($project, ['orders.ver']);

        $this->get('/bixosales/pedidos')->assertOk();
    }
}
