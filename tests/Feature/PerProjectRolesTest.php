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
 * FASE 7 — Perfiles por proyecto.
 *
 * Antes, `model_has_roles` no tenía dimensión de proyecto: un usuario solo podía
 * tener un rol a la vez EN TODO EL SISTEMA, y lo fijaba la última petición
 * atendida. `SetActiveProject` hacía `syncRoles([])` —borrado global— a quien no
 * tuviera ficha de empleado en el proyecto activo.
 *
 * Con el proyecto como «team» de Spatie, cada asignación vive en su negocio.
 */
class PerProjectRolesTest extends TestCase
{
    use RefreshDatabase;

    private Project $megahogar;
    private Project $tecsist;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['catalogo.ver', 'catalogo.administrar', 'pedidos.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->megahogar = $this->crearProyecto('megahogar-t');
        $this->tecsist   = $this->crearProyecto('tecsist-t');
    }

    private function crearProyecto(string $slug): Project
    {
        $p = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio '.$slug,
            'slug'      => $slug,
            'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo', 'is_active' => true]);
        $p->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        return $p;
    }

    /** Da de alta a alguien en un proyecto con un perfil concreto. */
    private function empleadoEn(User $u, Project $p, ?string $perfil): Employee
    {
        ProjectMember::firstOrCreate(['project_id' => $p->id, 'user_id' => $u->id], ['role' => 'viewer']);
        $e = Employee::create([
            'project_id' => $p->id, 'user_id' => $u->id,
            'name' => $u->name, 'spatie_role' => $perfil, 'is_active' => 1,
        ]);

        if ($perfil) {
            setPermissionsTeamId($p->id);
            $u->syncRoles([$perfil]);
        }

        return $e;
    }

    /** El caso que rompía todo: la misma persona en dos negocios con perfiles distintos. */
    public function test_una_persona_puede_tener_perfiles_distintos_en_cada_negocio(): void
    {
        Role::findOrCreate('Encargado', 'web')->syncPermissions(['catalogo.administrar']);
        Role::findOrCreate('Solo lectura', 'web')->syncPermissions(['catalogo.ver']);

        $u = User::factory()->create(['is_superadmin' => 0, 'name' => 'Ana']);
        $this->empleadoEn($u, $this->megahogar, 'Encargado');
        $this->empleadoEn($u, $this->tecsist,   'Solo lectura');

        setPermissionsTeamId($this->megahogar->id);
        $this->assertSame(['Encargado'], $u->fresh()->getRoleNames()->all());

        setPermissionsTeamId($this->tecsist->id);
        $this->assertSame(['Solo lectura'], $u->fresh()->getRoleNames()->all());
    }

    /**
     * Y el efecto práctico: administra el catálogo en un negocio y solo lo mira
     * en el otro, sin que una petición pise a la anterior.
     */
    public function test_los_permisos_efectivos_cambian_segun_el_negocio(): void
    {
        Role::findOrCreate('Encargado', 'web')->syncPermissions(['catalogo.administrar']);
        Role::findOrCreate('Solo lectura', 'web')->syncPermissions(['catalogo.ver']);

        $u = User::factory()->create(['is_superadmin' => 0]);
        $this->empleadoEn($u, $this->megahogar, 'Encargado');
        $this->empleadoEn($u, $this->tecsist,   'Solo lectura');

        setPermissionsTeamId($this->megahogar->id);
        $ana = $u->fresh();
        $this->assertTrue($ana->can('catalogo.administrar'), 'Debería administrar en MegaHogar.');

        setPermissionsTeamId($this->tecsist->id);
        $ana = $u->fresh();
        $this->assertTrue($ana->can('catalogo.ver'));
        $this->assertFalse($ana->can('catalogo.administrar'), 'No debería administrar en Tecsist.');
    }

    /**
     * El borrado silencioso: entrar a un negocio donde no tienes ficha ya NO
     * puede quitarte el perfil que tienes en otro.
     */
    public function test_entrar_a_otro_negocio_no_borra_el_perfil_del_primero(): void
    {
        Role::findOrCreate('Encargado', 'web')->syncPermissions(['catalogo.administrar']);

        $u = User::factory()->create(['is_superadmin' => 0]);
        $this->empleadoEn($u, $this->megahogar, 'Encargado');

        // Se le hace miembro de Tecsist pero SIN ficha de empleado: es
        // exactamente la situación de los 10 revendedores de producción.
        ProjectMember::create(['project_id' => $this->tecsist->id, 'user_id' => $u->id, 'role' => 'viewer']);

        $this->actingAs($u)->withSession(['active_project_id' => $this->tecsist->id])
            ->get('/bixoadmin/products');

        setPermissionsTeamId($this->megahogar->id);
        $this->assertSame(
            ['Encargado'],
            $u->fresh()->getRoleNames()->all(),
            'Visitar otro negocio borró el perfil que tenía en MegaHogar.'
        );
    }

    /** Cambiar el perfil desde RRHH solo afecta al negocio donde se cambia. */
    public function test_cambiar_el_perfil_en_un_negocio_no_toca_el_otro(): void
    {
        Permission::findOrCreate('hr.editar', 'web');
        Role::findOrCreate('Encargado', 'web')->syncPermissions(['catalogo.administrar']);
        Role::findOrCreate('Vendedor', 'web')->syncPermissions(['pedidos.ver']);
        Role::findOrCreate('jefe_rrhh_t', 'web')->syncPermissions(['hr.editar']);
        $m = Module::firstOrCreate(['key' => 'hr'], ['name' => 'Personal', 'is_active' => true]);
        $this->tecsist->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $ana = User::factory()->create(['is_superadmin' => 0]);
        $this->empleadoEn($ana, $this->megahogar, 'Encargado');
        $fichaTecsist = $this->empleadoEn($ana, $this->tecsist, 'Encargado');

        $jefe = User::factory()->create(['is_superadmin' => 0]);
        $this->empleadoEn($jefe, $this->tecsist, 'jefe_rrhh_t');

        $this->actingAs($jefe)->withSession(['active_project_id' => $this->tecsist->id])
            ->json('PUT', '/bixoadmin/hr/employees/'.$fichaTecsist->id, [
                'name' => $ana->name, 'spatie_role' => 'Vendedor', 'is_active' => true,
            ])->assertSuccessful();

        setPermissionsTeamId($this->tecsist->id);
        $this->assertSame(['Vendedor'], $ana->fresh()->getRoleNames()->all(), 'No se aplicó en Tecsist.');

        setPermissionsTeamId($this->megahogar->id);
        $this->assertSame(['Encargado'], $ana->fresh()->getRoleNames()->all(), 'Se contaminó MegaHogar.');
    }

    /** Los 13 roles heredados son globales (team_id NULL) y siguen encontrándose. */
    public function test_los_roles_globales_siguen_funcionando_en_cualquier_negocio(): void
    {
        $global = Role::findOrCreate('gerente', 'web');
        $global->team_id = null;
        $global->save();
        $global->syncPermissions(['catalogo.ver']);

        $u = User::factory()->create(['is_superadmin' => 0]);

        foreach ([$this->megahogar, $this->tecsist] as $p) {
            setPermissionsTeamId($p->id);
            $u->syncRoles(['gerente']);
            $this->assertTrue($u->fresh()->can('catalogo.ver'), "El rol global falló en {$p->slug}.");
        }
    }
}
