<?php

namespace Tests\Feature;

use App\Modules\Control\Models\AccessEvent;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\ProjectOwnership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASE 11 — Protección del Dueño.
 *
 * Reglas: un negocio siempre tiene dueño; el dueño puede entrar a su negocio;
 * y todo traspaso queda auditado. El Dueño (por negocio) no es lo mismo que el
 * Administrador de plataforma BIXO (`is_superadmin`, interno de Eskala).
 */
class ProjectOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $eskala;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eskala  = User::factory()->create(['is_superadmin' => 1, 'name' => 'Administrator']);
        $this->cliente = User::factory()->create(['is_superadmin' => 0, 'name' => 'Fany']);

        // Igual que en producción: el negocio nace a nombre de Eskala.
        $this->project = Project::create([
            'owner_id'  => $this->eskala->id,
            'name'      => 'MegaHogar',
            'slug'      => 'megahogar-test',
            'is_active' => true,
        ]);

        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->cliente->id, 'role' => 'viewer']);
    }

    public function test_traspasar_el_negocio_cambia_el_dueno(): void
    {
        ProjectOwnership::transferir($this->project, $this->cliente);

        $this->assertSame($this->cliente->id, $this->project->fresh()->owner_id);
    }

    public function test_el_traspaso_queda_auditado(): void
    {
        ProjectOwnership::transferir($this->project, $this->cliente);

        $evento = AccessEvent::where('action', 'owner_transferred')->latest('id')->first();

        $this->assertNotNull($evento, 'El traspaso de propiedad no dejó rastro.');
        $this->assertSame($this->project->id, $evento->project_id);
        $this->assertSame($this->cliente->id, $evento->target_user_id);
        $this->assertSame('Administrator', $evento->meta['from']);
        $this->assertSame('Fany', $evento->meta['to']);
    }

    /** El nuevo dueño tiene que poder entrar: si no era miembro, se le da la membresía. */
    public function test_el_nuevo_dueno_queda_como_miembro(): void
    {
        $otro = User::factory()->create(['name' => 'Sin membresía']);

        ProjectOwnership::transferir($this->project, $otro);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $this->project->id,
            'user_id'    => $otro->id,
        ]);
    }

    public function test_no_se_puede_traspasar_a_quien_ya_es_dueno(): void
    {
        $this->expectException(ValidationException::class);

        ProjectOwnership::transferir($this->project, $this->eskala);
    }

    /** Regla 1: un negocio no puede quedarse sin dueño. */
    public function test_el_dueno_no_puede_ser_retirado_del_proyecto(): void
    {
        ProjectOwnership::transferir($this->project, $this->cliente);
        $this->project->refresh();

        $this->assertFalse(ProjectOwnership::puedeSerRetirado($this->project, $this->cliente->id));
        $this->assertTrue(ProjectOwnership::puedeSerRetirado($this->project, $this->eskala->id));
    }

    /** Y la regla se aplica de verdad al borrar su ficha de empleado. */
    public function test_no_puedo_borrar_la_ficha_del_dueno(): void
    {
        ProjectOwnership::transferir($this->project, $this->cliente);
        $this->project->refresh();

        Permission::findOrCreate('hr.eliminar', 'web');
        Role::findOrCreate('rrhh_test', 'web')->syncPermissions(['hr.eliminar']);
        $m = Module::firstOrCreate(['key' => 'hr'], ['name' => 'Personal', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $fichaDelDueno = Employee::create([
            'project_id' => $this->project->id, 'user_id' => $this->cliente->id,
            'name' => 'Fany', 'spatie_role' => null, 'is_active' => 1,
        ]);

        $gestor = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $gestor->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $gestor->id,
            'name' => 'RRHH', 'spatie_role' => 'rrhh_test', 'is_active' => 1,
        ]);
        $gestor->syncRoles(['rrhh_test']);

        $this->actingAs($gestor)->withSession(['active_project_id' => $this->project->id])
            ->json('DELETE', '/bixoadmin/hr/employees/'.$fichaDelDueno->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('employees', ['id' => $fichaDelDueno->id]);
        $this->assertSame($this->cliente->id, $this->project->fresh()->owner_id);
    }

    /** Borrar la ficha de cualquier otra persona sigue funcionando. */
    public function test_si_puedo_borrar_la_ficha_de_alguien_que_no_es_dueno(): void
    {
        Permission::findOrCreate('hr.eliminar', 'web');
        Role::findOrCreate('rrhh_test2', 'web')->syncPermissions(['hr.eliminar']);
        $m = Module::firstOrCreate(['key' => 'hr'], ['name' => 'Personal', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $cualquiera = Employee::create([
            'project_id' => $this->project->id, 'user_id' => $this->cliente->id,
            'name' => 'Fany', 'spatie_role' => null, 'is_active' => 1,
        ]);

        $gestor = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $gestor->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $gestor->id,
            'name' => 'RRHH', 'spatie_role' => 'rrhh_test2', 'is_active' => 1,
        ]);
        $gestor->syncRoles(['rrhh_test2']);

        $this->actingAs($gestor)->withSession(['active_project_id' => $this->project->id])
            ->json('DELETE', '/bixoadmin/hr/employees/'.$cualquiera->id)
            ->assertSuccessful();
    }

    /** Solo el superadmin puede traspasar un negocio: es operación de plataforma. */
    public function test_el_traspaso_es_solo_del_superadmin(): void
    {
        $this->actingAs($this->cliente)
            ->post('/admin/projects/'.$this->project->id.'/owner', ['owner_id' => $this->cliente->id])
            ->assertRedirect();

        $this->assertSame($this->eskala->id, $this->project->fresh()->owner_id);
    }

    /** Ser dueño de un negocio no da nada sobre otro (§3.4). */
    public function test_ser_dueno_no_da_poder_sobre_otro_negocio(): void
    {
        ProjectOwnership::transferir($this->project, $this->cliente);

        $ajeno = Project::create([
            'owner_id'  => $this->eskala->id,
            'name'      => 'Tecsist',
            'slug'      => 'tecsist-test',
            'is_active' => true,
        ]);

        $this->assertTrue(ProjectOwnership::puedeSerRetirado($ajeno, $this->cliente->id));
        $this->assertNotSame($this->cliente->id, $ajeno->owner_id);
    }
}
