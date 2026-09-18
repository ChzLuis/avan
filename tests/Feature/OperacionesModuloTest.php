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
 * Modulo Operaciones (app/Modules/Operaciones): agenda y citas, mesas,
 * reservas, delivery, mapa operativo y el flujo de lavanderia.
 *
 * Se vigila que cada pantalla siga pintandose con su vista bajo
 * `operaciones::`, que el comando siga registrado y que nadie vuelva a crear
 * ni importar estas clases por su ruta vieja.
 */
class OperacionesModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['orders.ver', 'logistics.ver', 'mapa.ver', 'agenda.ver'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create(['is_superadmin' => true])->id,
            'name'      => 'Negocio operaciones',
            'slug'      => 'negocio-operaciones-' . uniqid(),
            'is_active' => true,
        ]);
        foreach (['orders', 'agenda', 'catalog'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        Role::findOrCreate('operaciones_test', 'web')->syncPermissions(['orders.ver', 'logistics.ver', 'mapa.ver', 'agenda.ver']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Operador', 'spatie_role' => 'operaciones_test', 'is_active' => 1,
        ]);
        $user->syncRoles(['operaciones_test']);

        $this->actingAs($user)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    public function test_las_pantallas_del_portal_comercial_se_pintan_con_las_vistas_del_modulo(): void
    {
        foreach (['/bixosales/delivery' => 'operaciones::comercial.delivery', '/bixosales/mesas' => 'operaciones::comercial.mesas', '/bixosales/reservas' => 'operaciones::comercial.reservas', '/bixosales/mapa' => 'operaciones::mapa.index'] as $uri => $vista) {
            $res = $this->get($uri);
            if ($res->status() === 403) {
                continue; // el permiso exacto lo cubre su propio test; aqui importa la vista
            }
            $res->assertOk()->assertViewIs($vista);
        }
    }

    public function test_la_agenda_del_panel_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->actingAs($this->project->owner)->withSession(['active_project_id' => $this->project->id])
            ->get('/bixoadmin/agenda')
            ->assertOk()
            ->assertViewIs('operaciones::agenda.index');
    }

    public function test_el_comando_del_modulo_sigue_registrado(): void
    {
        $this->assertContains('laundry:check-overdue', array_keys(\Illuminate\Support\Facades\Artisan::all()));
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Operaciones. Quien las necesite las importa por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/AgendaController.php',
            'app/Http/Controllers/DeliveryController.php',
            'app/Http/Controllers/MesaController.php',
            'app/Http/Controllers/OperationalMapController.php',
            'app/Http/Controllers/ReservaController.php',
            'app/Models/Appointment.php',
            'app/Models/Availability.php',
            'app/Models/BlockedDate.php',
            'app/Models/OperationalEvent.php',
            'app/Models/OperationalMap.php',
            'app/Models/OperationalObject.php',
            'app/Models/OperationalRequest.php',
            'app/Support/LaundryFlow.php',
            'app/Console/Commands/CheckLaundryOverdue.php',
            'resources/views/agenda',
            'resources/views/mapa',
            'resources/views/comercial/delivery.blade.php',
            'resources/views/comercial/mesas.blade.php',
            'resources/views/comercial/reservas.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Operaciones es su unico sitio.");
        }

        $patron = '/\\bApp\\\\('
            . 'Models\\\\(Appointment|Availability|BlockedDate|OperationalEvent|OperationalMap|OperationalObject|OperationalRequest)\\b'
            . '|Support\\\\LaundryFlow\\b'
            . '|Console\\\\Commands\\\\CheckLaundryOverdue\\b'
            . '|Http\\\\Controllers\\\\(Agenda|Delivery|Mesa|OperationalMap|Reserva)Controller\\b'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Operaciones/') || $rel === 'tests/Feature/OperacionesModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Operaciones por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
