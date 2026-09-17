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
 * Modulo Personas (app/Modules/Personas): el primero de la mudanza por dominios.
 *
 * Dos cosas se vigilan aqui. Que las pantallas del modulo sigan pintandose
 * con sus vistas bajo el espacio de nombres `personas::` (es lo unico que la
 * mudanza cambio de cara al usuario), y que nadie vuelva a crear estas clases
 * en su sitio antiguo ni las referencie por la ruta vieja: la frontera del
 * modulo la hace cumplir este test, no un documento.
 */
class PersonasModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['hr.ver', 'roles.ver', 'attendance.ver'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio personas',
            'slug'      => 'negocio-personas',
            'is_active' => true,
        ]);

        Role::findOrCreate('personas_test', 'web')->syncPermissions(['hr.ver', 'roles.ver', 'attendance.ver']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Persona', 'spatie_role' => 'personas_test', 'is_active' => 1,
        ]);
        $user->syncRoles(['personas_test']);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);
    }

    public function test_empleados_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixoadmin/hr/employees')
            ->assertOk()
            ->assertViewIs('personas::hr.employees');
    }

    public function test_perfiles_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixoadmin/roles')
            ->assertOk()
            ->assertViewIs('personas::roles.index');
    }

    public function test_grupos_responde(): void
    {
        $this->get('/bixoadmin/company/groups')->assertSuccessful();
    }

    /**
     * Frontera del modulo: las clases viven SOLO en app/Modules/Personas.
     * Si alguien las vuelve a crear en app/Models o app/Http/Controllers, o
     * las importa por la ruta vieja, esto avisa antes de que lleguen dos
     * copias a produccion.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/HRController.php',
            'app/Http/Controllers/AttendanceController.php',
            'app/Http/Controllers/UserGroupController.php',
            'app/Http/Controllers/RolePermissionController.php',
            'app/Models/Attendance.php',
            'app/Models/WorkSchedule.php',
            'app/Models/UserGroup.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Personas es su unico sitio.");
        }

        $patron = '/\bApp\\\\(Models\\\\(Attendance|WorkSchedule|UserGroup)|Http\\\\Controllers\\\\(HR|Attendance|UserGroup|RolePermission)Controller)\b/';
        $infractores = [];
        $raices = [base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap')];
        foreach ($raices as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Personas/') || $rel === 'tests/Feature/PersonasModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Personas por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
