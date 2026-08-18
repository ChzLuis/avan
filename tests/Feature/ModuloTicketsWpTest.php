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
 * "Tickets manuales" es un encargo a medida de un solo cliente: habla con el
 * WordPress de pruebatusuerte.com.pe. Se pintaba en el menu de cualquier
 * panel de superadmin y su ruta solo pedia `tickets.ver`, asi que desde
 * cualquier otro negocio se podian listar —y borrar— tickets ajenos.
 *
 * Ahora lo decide el proyecto (`modulo_tickets_wp`), no quien mira.
 */
class ModuloTicketsWpTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(bool $conModulo): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio QA', 'slug' => 'tickets-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        if ($conModulo) {
            $p->settings()->create(['key' => 'modulo_tickets_wp', 'value' => '1']);
        }
        return $p;
    }

    private function entrar(Project $p): User
    {
        Permission::findOrCreate('tickets.ver', 'web');
        Permission::findOrCreate('tickets.eliminar', 'web');
        Role::findOrCreate('tk_admin', 'web')->syncPermissions(['tickets.ver', 'tickets.eliminar']);
        $u = User::factory()->create(['is_superadmin' => 1]);
        ProjectMember::create(['project_id' => $p->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $p->id, 'user_id' => $u->id,
            'name' => 'Admin', 'spatie_role' => 'tk_admin', 'is_active' => 1]);
        $u->syncRoles(['tk_admin']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $p->id,
            'active_project_id'    => $p->id,
        ]);
        return $u;
    }

    public function test_un_negocio_sin_el_modulo_no_alcanza_los_tickets_ni_por_url(): void
    {
        $p = $this->proyecto(false);
        $this->entrar($p);

        $this->get('/bixosales/tickets-manuales')->assertNotFound();
        $this->get('/bixosales/tickets-manuales/buscar?dni=12345678')->assertNotFound();
        $this->post('/bixosales/tickets-manuales/eliminar', ['codigo' => 'X'])->assertNotFound();
    }

    public function test_el_menu_no_ensena_tickets_en_un_negocio_que_no_lo_encargo(): void
    {
        $p = $this->proyecto(false);
        $this->entrar($p);

        $this->get('/bixosales')
            ->assertOk()
            ->assertDontSee('Tickets manuales')
            ->assertDontSee('tickets-manuales');
    }

    public function test_una_ferreteria_no_lee_la_palabra_mesas_en_su_buscador(): void
    {
        $p = $this->proyecto(false);
        $this->entrar($p);

        $this->get('/bixosales')
            ->assertOk()
            ->assertDontSee('Buscar clientes, órdenes, mesas...', false);
    }

    public function test_el_proyecto_que_si_lo_encargo_conserva_su_modulo(): void
    {
        $p = $this->proyecto(true);
        $this->entrar($p);

        $this->get('/bixosales')->assertOk()->assertSee('Tickets manuales');
    }
}
