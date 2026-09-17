<?php

namespace Tests\Feature;

use App\Modules\Control\Models\AccessEvent;
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
 * FASE 12 — Auditoría de cambios de acceso.
 *
 * El dueño de un negocio tiene que poder responder «¿quién le dio acceso a Caja
 * a Pedro?». Hasta ahora ningún cambio de perfil dejaba rastro consultable.
 *
 * Solo se auditan cambios DELIBERADOS. La sincronización automática de roles que
 * hace SetActiveProject en cada petición no se registra: inundaría la tabla y no
 * es la decisión de nadie.
 */
class AccessAuditTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['roles.ver', 'roles.gestionar', 'hr.ver', 'hr.crear', 'hr.editar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio auditoría',
            'slug'      => 'negocio-auditoria',
            'is_active' => true,
        ]);

        foreach (['hr', 'settings'] as $k) {
            $m = Module::firstOrCreate(['key' => $k], ['name' => ucfirst($k), 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        Role::findOrCreate('admin_auditoria_test', 'web')
            ->syncPermissions(['roles.ver', 'roles.gestionar', 'hr.ver', 'hr.crear', 'hr.editar']);

        $this->admin = User::factory()->create(['is_superadmin' => 0, 'name' => 'Luis']);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->admin->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $this->admin->id,
            'name' => 'Luis', 'spatie_role' => 'admin_auditoria_test', 'is_active' => 1,
        ]);
        $this->admin->syncRoles(['admin_auditoria_test']);

        $this->actingAs($this->admin)->withSession(['active_project_id' => $this->project->id]);
    }

    public function test_crear_un_perfil_queda_registrado(): void
    {
        $this->json('POST', '/bixoadmin/roles', [
            'name' => 'Encargado', 'permissions' => ['roles.ver'],
        ])->assertSuccessful();

        $evento = AccessEvent::latest('id')->first();

        $this->assertNotNull($evento, 'Crear un perfil no dejó rastro.');
        $this->assertSame('role_created', $evento->action);
        $this->assertSame('Encargado', $evento->role_name);
        $this->assertSame($this->admin->id, $evento->actor_id);
        $this->assertSame($this->project->id, $evento->project_id);
    }

    public function test_cambiar_permisos_registra_que_se_amplio_y_que_se_recorto(): void
    {
        $rol = Role::findOrCreate('Vendedor', 'web');
        $rol->syncPermissions(['roles.ver', 'hr.ver']);

        $this->json('PUT', '/bixoadmin/roles/'.$rol->id, [
            'permissions' => ['roles.ver', 'hr.editar'],   // +hr.editar  −hr.ver
        ])->assertSuccessful();

        $evento = AccessEvent::where('action', 'permissions_changed')->latest('id')->first();

        $this->assertNotNull($evento);
        $this->assertSame(['hr.editar'], $evento->meta['added']);
        $this->assertSame(['hr.ver'], $evento->meta['removed']);
        $this->assertStringContainsString('(+1, −1)', $evento->label);
    }

    public function test_renombrar_un_perfil_queda_registrado(): void
    {
        $rol = Role::findOrCreate('Cajero', 'web');

        $this->json('PUT', '/bixoadmin/roles/'.$rol->id, [
            'name' => 'Cajera', 'permissions' => [],
        ])->assertSuccessful();

        $evento = AccessEvent::where('action', 'role_renamed')->latest('id')->first();

        $this->assertNotNull($evento);
        $this->assertSame('Cajero', $evento->meta['from']);
        $this->assertSame('Cajera', $evento->meta['to']);
    }

    public function test_eliminar_un_perfil_queda_registrado(): void
    {
        $rol = Role::findOrCreate('Temporal', 'web');

        $this->json('DELETE', '/bixoadmin/roles/'.$rol->id)->assertSuccessful();

        $this->assertSame(
            1,
            AccessEvent::where('action', 'role_deleted')->where('role_name', 'Temporal')->count()
        );
    }

    public function test_asignar_un_perfil_a_una_persona_queda_registrado(): void
    {
        $pedro = User::factory()->create(['name' => 'Pedro']);
        $empleado = Employee::create([
            'project_id' => $this->project->id, 'user_id' => $pedro->id,
            'name' => 'Pedro', 'spatie_role' => null, 'is_active' => 1,
        ]);
        Role::findOrCreate('Vendedor', 'web');

        $this->json('PUT', '/bixoadmin/hr/employees/'.$empleado->id, [
            'name' => 'Pedro', 'spatie_role' => 'Vendedor', 'is_active' => true,
        ])->assertSuccessful();

        $evento = AccessEvent::where('action', 'profile_assigned')->latest('id')->first();

        $this->assertNotNull($evento, 'Asignar un perfil a una persona no dejó rastro.');
        $this->assertSame('Vendedor', $evento->role_name);
        $this->assertSame($pedro->id, $evento->target_user_id);
        $this->assertSame('Luis asignó el perfil «Vendedor» a Pedro', $evento->label);
    }

    public function test_retirar_el_perfil_de_una_persona_queda_registrado(): void
    {
        $pedro = User::factory()->create(['name' => 'Pedro']);
        Role::findOrCreate('Vendedor', 'web');
        $empleado = Employee::create([
            'project_id' => $this->project->id, 'user_id' => $pedro->id,
            'name' => 'Pedro', 'spatie_role' => 'Vendedor', 'is_active' => 1,
        ]);

        $this->json('PUT', '/bixoadmin/hr/employees/'.$empleado->id, [
            'name' => 'Pedro', 'spatie_role' => null, 'is_active' => true,
        ])->assertSuccessful();

        $evento = AccessEvent::where('action', 'profile_removed')->latest('id')->first();

        $this->assertNotNull($evento);
        $this->assertSame('Vendedor', $evento->meta['from']);
    }

    /** Guardar sin tocar el perfil no debe ensuciar la auditoría. */
    public function test_editar_otros_datos_no_genera_evento_de_acceso(): void
    {
        $pedro = User::factory()->create(['name' => 'Pedro']);
        Role::findOrCreate('Vendedor', 'web');
        $empleado = Employee::create([
            'project_id' => $this->project->id, 'user_id' => $pedro->id,
            'name' => 'Pedro', 'spatie_role' => 'Vendedor', 'is_active' => 1,
        ]);

        AccessEvent::query()->delete();

        $this->json('PUT', '/bixoadmin/hr/employees/'.$empleado->id, [
            'name' => 'Pedro Ramírez', 'spatie_role' => 'Vendedor', 'is_active' => true,
        ])->assertSuccessful();

        $this->assertSame(0, AccessEvent::count(), 'Se registró un cambio de acceso que no ocurrió.');
    }

    /** La auditoría nunca puede tumbar la operación que la origina. */
    public function test_un_fallo_de_auditoria_no_rompe_la_operacion(): void
    {
        \Illuminate\Support\Facades\Schema::drop('access_events');

        $this->json('POST', '/bixoadmin/roles', [
            'name' => 'Pese a todo', 'permissions' => [],
        ])->assertSuccessful();

        $this->assertTrue(Role::where('name', 'Pese a todo')->exists());
    }
}
