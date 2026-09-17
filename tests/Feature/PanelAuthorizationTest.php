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
 * Autorizacion de Roles, Agenda y Proveedores.
 *
 * El caso grave que se protege es la ESCALADA DE PRIVILEGIOS: las rutas de roles
 * no exigian ningun permiso, y RolePermissionController@update hace
 * syncPermissions(). Cualquier miembro del proyecto —un vendedor, un lector—
 * podia concederse todos los permisos del sistema con un solo PUT. Y como los
 * roles de Spatie son globales, el cambio afectaba a todos los negocios.
 */
class PanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Role $rolVictima;
    private \App\Models\Appointment $cita;
    private \App\Modules\Inventario\Models\Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'roles.ver', 'roles.gestionar',
            'agenda.ver', 'agenda.crear', 'agenda.editar',
            'proveedores.ver', 'proveedores.editar',
            'catalog.ver',
        ] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        // El proyecto pertenece a OTRA persona: si el dueño fuera el usuario de
        // prueba, Gate::before le daria todos los permisos y el test no probaria nada.
        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio panel',
            'slug'      => 'negocio-panel',
            'is_active' => true,
        ]);

        foreach (['agenda', 'catalog'] as $key) {
            $module = Module::firstOrCreate(['key' => $key], ['name' => ucfirst($key), 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
        }

        $this->rolVictima = Role::findOrCreate('gerente_objetivo', 'web');
        $this->rolVictima->syncPermissions(['catalog.ver']);

        // Registros reales: con ids inexistentes el route model binding responde
        // 404 antes de que llegue a evaluarse el permiso, y el test no probaria nada.
        $this->cita = \App\Models\Appointment::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente',
            'date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'pending',
        ]);
        $this->proveedor = \App\Modules\Inventario\Models\Proveedor::create([
            'project_id' => $this->project->id, 'name' => 'Proveedor SA', 'is_active' => true,
        ]);
    }

    private function usuarioConRol(string $rol, array $permisos): User
    {
        Role::findOrCreate($rol, 'web')->syncPermissions($permisos);

        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Empleado '.$rol, 'spatie_role' => $rol, 'is_active' => 1,
        ]);
        $user->syncRoles([$rol]);

        return $user;
    }

    private function comoUsuario(User $user): self
    {
        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);
        return $this;
    }

    /** El caso que motiva todo: un lector no puede reescribir los permisos de un rol. */
    public function test_un_miembro_sin_autoridad_no_puede_concederse_permisos(): void
    {
        $this->comoUsuario($this->usuarioConRol('vendedor_test', ['catalog.ver', 'roles.ver']))
            ->json('PUT', '/bixoadmin/roles/'.$this->rolVictima->id, [
                'permissions' => ['catalog.ver', 'catalog.eliminar', 'roles.gestionar'],
            ])->assertForbidden();

        $this->assertSame(
            ['catalog.ver'],
            $this->rolVictima->fresh()->permissions->pluck('name')->all(),
            'Los permisos del rol cambiaron pese al 403: la escalada sigue abierta.'
        );
    }

    /** @dataProvider mutacionesDeRoles */
    public function test_mutar_roles_exige_roles_gestionar(string $verbo, string $ruta): void
    {
        $this->comoUsuario($this->usuarioConRol('lector_roles_test', ['roles.ver']))
            ->json($verbo, str_replace('{role}', (string) $this->rolVictima->id, $ruta), ['name' => 'nuevo'])
            ->assertForbidden();
    }

    public static function mutacionesDeRoles(): array
    {
        return [
            'crear rol'  => ['POST',   '/bixoadmin/roles'],
            'editar rol' => ['PUT',    '/bixoadmin/roles/{role}'],
            'borrar rol' => ['DELETE', '/bixoadmin/roles/{role}'],
        ];
    }

    public function test_quien_tiene_roles_gestionar_si_puede_mutar(): void
    {
        $this->comoUsuario($this->usuarioConRol('admin_roles_test', ['roles.ver', 'roles.gestionar']))
            ->json('PUT', '/bixoadmin/roles/'.$this->rolVictima->id, [
                'permissions' => ['catalog.ver', 'agenda.ver'],
            ])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['catalog.ver', 'agenda.ver'],
            $this->rolVictima->fresh()->permissions->pluck('name')->all()
        );
    }

    /** @dataProvider escriturasConPermisoDeLectura */
    public function test_ver_no_autoriza_escribir(string $verbo, string $ruta, string $permisoLectura): void
    {
        $ruta = str_replace(
            ['{appointment}', '{proveedor}'],
            [(string) $this->cita->id, (string) $this->proveedor->id],
            $ruta
        );

        $this->comoUsuario($this->usuarioConRol('lector_'.md5($ruta.$verbo), [$permisoLectura]))
            ->json($verbo, $ruta, [])
            ->assertForbidden();
    }

    public static function escriturasConPermisoDeLectura(): array
    {
        return [
            'crear cita'         => ['POST',   '/bixoadmin/appointments',                          'agenda.ver'],
            'editar cita'        => ['PUT',    '/bixoadmin/appointments/{appointment}',            'agenda.ver'],
            'borrar cita'        => ['DELETE', '/bixoadmin/appointments/{appointment}',            'agenda.ver'],
            'crear proveedor'    => ['POST',   '/bixoadmin/company/proveedores',                   'proveedores.ver'],
            'editar proveedor'   => ['PUT',    '/bixoadmin/company/proveedores/{proveedor}',       'proveedores.ver'],
            'borrar proveedor'   => ['DELETE', '/bixoadmin/company/proveedores/{proveedor}',       'proveedores.ver'],
            'importar proveedor' => ['POST',   '/bixoadmin/company/proveedores/import',  'proveedores.ver'],
        ];
    }

    /** El dueño del proyecto conserva todo por Gate::before, sin permisos explicitos. */
    public function test_el_dueno_del_proyecto_no_queda_bloqueado(): void
    {
        $dueno = User::find($this->project->owner_id);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $dueno->id, 'role' => 'owner']);

        $this->comoUsuario($dueno)
            ->json('PUT', '/bixoadmin/roles/'.$this->rolVictima->id, ['permissions' => ['catalog.ver']])
            ->assertSuccessful();
    }
}
