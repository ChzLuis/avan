<?php

namespace Tests\Feature;

use App\Models\Client;
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
 * Seguridad de Clientes (UX2). El resource del panel iba ENTERO bajo
 * can:clients.ver: un lector podia crear, editar y BORRAR clientes, y
 * moveStage (PATCH) tambien mutaba con permiso de lectura. Es la misma clase
 * de agujero que el hotfix "*.ver ya no autoriza escribir" cerro en Pedidos y
 * Cotizaciones — Clientes quedo fuera de aquel barrido.
 */
class ClientRoutesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Clientes QA', 'slug' => 'clientes-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'clients'], ['name' => 'clients', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function entrar(array $permisos): User
    {
        $rol = Role::findOrCreate('cli_' . md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);

        return $u;
    }

    private function cliente(): Client
    {
        return Client::create(['project_id' => $this->project->id, 'name' => 'Cliente X']);
    }

    public function test_el_lector_puede_ver_pero_no_escribir(): void
    {
        $this->entrar(['clients.ver']);
        $c = $this->cliente();

        $this->get('/bixoadmin/clients')->assertSuccessful();

        // EL AGUJERO: antes estos cuatro pasaban con solo clients.ver.
        $this->post('/bixoadmin/clients', ['name' => 'Nuevo'])->assertStatus(403);
        $this->put("/bixoadmin/clients/{$c->id}", ['name' => 'Cambiado'])->assertStatus(403);
        $this->delete("/bixoadmin/clients/{$c->id}")->assertStatus(403);
        $this->patch("/bixoadmin/clients/{$c->id}/stage", ['stage' => 'won'])->assertStatus(403);

        $this->assertSame('Cliente X', $c->fresh()->name, 'el lector no mutó nada');
        $this->assertNotNull(Client::find($c->id), 'ni borró');
    }

    public function test_cada_permiso_habilita_solo_su_accion(): void
    {
        $c = $this->cliente();

        $this->entrar(['clients.ver', 'clients.crear']);
        // El contrato es de AUTORIZACION (nunca 403 con el permiso correcto);
        // la forma de la respuesta (200/302) es del controlador, no de esta prueba.
        $this->assertNotSame(403, $this->post('/bixoadmin/clients', ['name' => 'Creado'])->getStatusCode());
        $this->put("/bixoadmin/clients/{$c->id}", ['name' => 'Y'])->assertStatus(403); // editar no
        $this->delete("/bixoadmin/clients/{$c->id}")->assertStatus(403);               // borrar no

        $this->entrar(['clients.ver', 'clients.editar']);
        $this->assertNotSame(403, $this->put("/bixoadmin/clients/{$c->id}", ['name' => 'Editado'])->getStatusCode());
        $this->assertNotSame(403, $this->patch("/bixoadmin/clients/{$c->id}/stage", ['stage' => 'contacted'])->getStatusCode());
        $this->delete("/bixoadmin/clients/{$c->id}")->assertStatus(403);

        $this->entrar(['clients.ver', 'clients.eliminar']);
        $this->assertNotSame(403, $this->delete("/bixoadmin/clients/{$c->id}")->getStatusCode());
        $this->assertNull(Client::find($c->id));
    }

    public function test_bixosales_conserva_su_dual_granular(): void
    {
        // La otra superficie ya estaba bien: se fija con test para que no
        // retroceda.
        $this->entrar(['clients.ver']);
        $c = $this->cliente();

        $this->getJson('/bixosales/clientes')->assertSuccessful();
        $this->postJson('/bixosales/clientes', ['name' => 'N'])->assertStatus(403);
        $this->deleteJson("/bixosales/clientes/{$c->id}")->assertStatus(403);
    }
    /**
     * `clients.show` estaba declarada apuntando a un metodo inexistente:
     * `/clients/{id}` devolvia **500 en produccion** (verificado en ARIN el
     * 2026-08-16). Ninguna vista enlazaba ahi, por eso nadie lo vio.
     */
    public function test_la_ficha_del_cliente_ya_no_revienta(): void
    {
        $this->entrar(['clients.ver']);
        $cliente = $this->project->clients()->create(['name' => 'Ficha QA']);

        $this->get("/bixoadmin/clients/{$cliente->id}")->assertSuccessful();
        $this->getJson("/bixoadmin/clients/{$cliente->id}")->assertSuccessful()
            ->assertJsonStructure(['cliente', 'resumen' => ['pedidos', 'vendido', 'deuda'], 'pedidos', 'cotizaciones']);
    }

    /** La ficha consolida la deuda real del cliente, derivada del libro. */
    public function test_la_ficha_dice_cuanto_debe_el_cliente(): void
    {
        $this->entrar(['clients.ver']);
        $cliente = $this->project->clients()->create(['name' => 'Deudor']);
        \App\Models\Order::create([
            'project_id' => $this->project->id, 'client_id' => $cliente->id,
            'client_name' => 'Deudor', 'status' => 'pending',
            'payment_status' => 'pending', 'total' => '1500.00',
        ]);

        $r = $this->getJson("/bixoadmin/clients/{$cliente->id}")->assertSuccessful();
        $this->assertSame('1,500.00', $r->json('resumen.deuda'));
        $this->assertSame(1, $r->json('resumen.pedidos'));
    }

    /** Y no se puede espiar la ficha de un cliente de otro proyecto. */
    public function test_no_se_ve_la_ficha_de_otro_proyecto(): void
    {
        $this->entrar(['clients.ver']);
        $otro = \App\Models\Project::create([
            'owner_id' => \App\Models\User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-fichas', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajeno = $otro->clients()->create(['name' => 'Ajeno']);

        $r = $this->getJson("/bixoadmin/clients/{$ajeno->id}");
        $this->assertContains($r->status(), [403, 404]);
    }

}
