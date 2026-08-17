<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASE 1 del plan de Perfiles y Accesos: la descripción del rol se guarda.
 *
 * Antes, la pantalla tenía el campo, el navegador lo enviaba y el controlador lo
 * validaba, pero la tabla `roles` no tenía la columna: se descartaba en cada
 * guardado y al recargar siempre salía vacío.
 *
 * Estos tests NO comprueban permisos. Solo persistencia.
 */
class RoleDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['roles.ver', 'roles.gestionar'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio perfiles',
            'slug'      => 'negocio-perfiles',
            'is_active' => true,
        ]);
    }

    private function gestorDeRoles(): User
    {
        Role::findOrCreate('gestor_roles_test', 'web')->syncPermissions(['roles.ver', 'roles.gestionar']);

        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Gestor', 'spatie_role' => 'gestor_roles_test', 'is_active' => 1,
        ]);
        $user->syncRoles(['gestor_roles_test']);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);

        return $user;
    }

    public function test_crear_un_perfil_guarda_su_descripcion(): void
    {
        $this->gestorDeRoles();

        $this->json('POST', '/bixoadmin/roles', [
            'name'        => 'Encargado',
            'description' => 'Gestiona la operación diaria del negocio.',
            'permissions' => ['roles.ver'],
        ])->assertSuccessful();

        $this->assertDatabaseHas('roles', [
            'name'        => 'Encargado',
            'description' => 'Gestiona la operación diaria del negocio.',
        ]);
    }

    /** El criterio de cierre de la fase: crear, recargar la pantalla y que siga ahí. */
    public function test_la_descripcion_sobrevive_a_recargar_la_pantalla(): void
    {
        $this->gestorDeRoles();

        $this->json('POST', '/bixoadmin/roles', [
            'name'        => 'Almacén',
            'description' => 'Gestiona productos e inventario.',
            'permissions' => [],
        ])->assertSuccessful();

        $html = $this->get('/bixoadmin/roles')->assertSuccessful()->getContent();

        $this->assertStringContainsString(
            'Gestiona productos e inventario.',
            $html,
            'La pantalla de Roles no devuelve la descripción guardada.'
        );
    }

    public function test_editar_un_perfil_cambia_su_descripcion(): void
    {
        $this->gestorDeRoles();
        $rol = Role::findOrCreate('Vendedor', 'web');
        $rol->description = 'Texto viejo.';
        $rol->save();

        $this->json('PUT', '/bixoadmin/roles/'.$rol->id, [
            'description' => 'Vende, cotiza y atiende clientes.',
            'permissions' => [],
        ])->assertSuccessful();

        $this->assertSame('Vende, cotiza y atiende clientes.', $rol->fresh()->description);
    }

    /** Una petición que no manda el campo no debe borrar lo que ya había. */
    public function test_no_enviar_la_descripcion_no_la_borra(): void
    {
        $this->gestorDeRoles();
        $rol = Role::findOrCreate('Contador', 'web');
        $rol->description = 'Gestiona caja, cobros y facturación.';
        $rol->save();

        $this->json('PUT', '/bixoadmin/roles/'.$rol->id, ['permissions' => ['roles.ver']])
            ->assertSuccessful();

        $this->assertSame('Gestiona caja, cobros y facturación.', $rol->fresh()->description);
    }

    /** Crear dos veces el mismo nombre debe actualizar la descripción, no ignorarla. */
    public function test_guardar_sobre_un_rol_existente_actualiza_la_descripcion(): void
    {
        $this->gestorDeRoles();
        Role::findOrCreate('Solo lectura', 'web');

        $this->json('POST', '/bixoadmin/roles', [
            'name'        => 'Solo lectura',
            'description' => 'Consulta información sin realizar modificaciones.',
            'permissions' => [],
        ])->assertSuccessful();

        $this->assertDatabaseHas('roles', [
            'name'        => 'Solo lectura',
            'description' => 'Consulta información sin realizar modificaciones.',
        ]);
    }
}
