<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La trampa del NOT IN con nulos.
 *
 * En SQL, `columna NOT IN (...)` no evalua a verdadero cuando la columna es
 * NULL: evalua a NULL, y la fila se descarta en silencio. Cada consulta que
 * filtra "todo menos estos estados" sobre una columna que admite nulos pierde
 * filas sin avisar. Ya mordio dos veces en produccion:
 *
 *   · Cuentas por Cobrar ocultaba 3 pedidos = S/ 8 298,00 de deuda real.
 *   · El KPI de pedidos de WhatsApp marcaba 0 teniendo 3 pendientes.
 *
 * Estos contratos fijan el comportamiento correcto: un nulo significa "nadie
 * lo ha marcado todavia", que es justo lo que se quiere contar.
 */
class NotInNullTrapTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'reports.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Nulos QA', 'slug' => 'nulos-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'orders'], ['name' => 'orders', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        Cache::flush();   // el dashboard cachea los KPI 120s
    }

    private function entrar(): User
    {
        $rol = Role::findOrCreate('nulos_lector', 'web');
        $rol->syncPermissions(['orders.ver', 'reports.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);

        return $u;
    }

    /**
     * El webhook del bot crea pedidos de canal whatsapp sin rellenar wa_status.
     * Ese nulo es "aun no entregado", no "no aplica": el KPI debe contarlos.
     */
    public function test_el_kpi_de_whatsapp_cuenta_los_pedidos_sin_wa_status(): void
    {
        $this->entrar();

        Order::create(['project_id' => $this->project->id, 'client_name' => 'Sin marcar',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '100.00',
            'sales_channel' => 'whatsapp', 'wa_status' => null]);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'En camino',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '100.00',
            'sales_channel' => 'whatsapp', 'wa_status' => 'en_camino']);
        // Entregado: fuera del pendiente.
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Entregado',
            'status' => 'done', 'payment_status' => 'paid', 'total' => '100.00',
            'sales_channel' => 'whatsapp', 'wa_status' => 'entregado']);
        // Otro canal: no es asunto de este KPI.
        Order::create(['project_id' => $this->project->id, 'client_name' => 'POS',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '100.00',
            'sales_channel' => 'pos', 'wa_status' => null]);

        $r = $this->get('/bixosales')->assertSuccessful();

        $this->assertSame(2, $r->viewData('waPendientes'),
            'el nulo cuenta como pendiente; entregado y otros canales no');
    }
}
