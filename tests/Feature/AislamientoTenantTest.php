<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\RifaVenta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Aislamiento multiempresa (RISK-001 y plantilla de TD-002).
 *
 * La regla: un registro de otro negocio NI SE ENCUENTRA. No es un 403 de
 * cortesía — para el intruso ese ID no existe (404). Estos contratos fijan
 * el primer caso corregido (RifaVenta, cuyos métodos recibían la venta por
 * la URL sin comprobar el proyecto) y sirven de plantilla para cubrir el
 * resto de entidades, una por una, en la Fase 1.
 */
class AislamientoTenantTest extends TestCase
{
    use RefreshDatabase;

    private Project $mio;
    private Project $ajeno;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'rifas.cancelar', 'rifas.validar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->mio = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio Propio', 'slug' => 'propio-qa', 'category' => 'rifa', 'is_active' => true,
        ]);
        $this->ajeno = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio Ajeno', 'slug' => 'ajeno-qa', 'category' => 'rifa', 'is_active' => true,
        ]);
    }

    private function ventaDe(Project $proyecto): RifaVenta
    {
        return RifaVenta::allProjects()->create([
            'project_id' => $proyecto->id,
            'order_number' => 'RF-'.$proyecto->id.'-001',
            'wa_number' => '51999888777', 'plan' => 'basico', 'plan_nombre' => 'Básico',
            'tickets' => 3, 'monto' => 30, 'nombre' => 'Cliente '.$proyecto->name,
            'status' => 'pendiente',
        ]);
    }

    private function entrarComo(Project $proyecto): void
    {
        Role::findOrCreate('tenant_qa', 'web')->syncPermissions(['orders.ver', 'rifas.cancelar', 'rifas.validar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $proyecto->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $proyecto->id, 'user_id' => $u->id,
            'name' => 'Empleado QA', 'spatie_role' => 'tenant_qa', 'is_active' => 1]);
        $u->syncRoles(['tenant_qa']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $proyecto->id,
            'active_project_id'    => $proyecto->id,
        ]);
    }

    /** El hueco original: cancelar la venta de OTRO negocio cambiando el ID. */
    public function test_cancelar_una_venta_ajena_devuelve_404(): void
    {
        $ventaAjena = $this->ventaDe($this->ajeno);
        $this->entrarComo($this->mio);

        $this->post('/bixosales/pedidos-bot/'.$ventaAjena->id.'/cancelar')
            ->assertNotFound();

        $this->assertSame('pendiente', RifaVenta::allProjects()->find($ventaAjena->id)->status,
            'La venta ajena no debe cambiar de estado');
    }

    public function test_cancelar_una_venta_propia_sigue_funcionando(): void
    {
        $ventaMia = $this->ventaDe($this->mio);
        $this->entrarComo($this->mio);

        $this->post('/bixosales/pedidos-bot/'.$ventaMia->id.'/cancelar')
            ->assertSuccessful();

        $this->assertSame('cancelado', RifaVenta::allProjects()->find($ventaMia->id)->status);
    }

    /** El scope filtra las consultas: la venta ajena ni aparece en listados. */
    public function test_el_scope_esconde_las_ventas_de_otros_negocios(): void
    {
        $this->ventaDe($this->mio);
        $this->ventaDe($this->ajeno);
        $this->entrarComo($this->mio);

        // Con sesion del proyecto propio, la consulta normal solo ve lo propio.
        $this->get('/bixosales'); // fija la sesion en el request
        $visibles = RifaVenta::count();
        $todas    = RifaVenta::allProjects()->count();

        $this->assertSame(1, $visibles, 'Solo la venta del proyecto en sesión');
        $this->assertSame(2, $todas, 'El escape explícito sí ve todo');
    }

    /** Los endpoints del bot no llevan sesión: el scope no les aplica y el
     *  bot de rifas en producción sigue encontrando sus ventas. */
    public function test_sin_sesion_el_bot_sigue_encontrando_la_venta(): void
    {
        $venta = $this->ventaDe($this->ajeno);

        // Sin actingAs ni sesión: así llegan los webhooks del bot.
        $encontrada = RifaVenta::find($venta->id);

        $this->assertNotNull($encontrada, 'Sin sesión de proyecto el scope es neutro (comportamiento actual del bot)');
    }

    /** Las 14 entidades tenant-owned llevan el scope cableado: si alguien le
     *  quita el trait a una, este contrato lo delata. Se comprueba que el SQL
     *  que genera cada modelo filtra por project_id cuando hay sesión. */
    public function test_toda_entidad_nuclear_filtra_por_proyecto_cuando_hay_sesion(): void
    {
        session(['active_project_id' => $this->mio->id]);

        $modelos = [
            \App\Models\Caja::class, \App\Models\CatalogIntegration::class,
            \App\Models\Client::class, \App\Models\Combo::class,
            \App\Models\GuiaRemision::class, \App\Modules\Inventario\Models\InventoryMovement::class,
            \App\Models\Invoice::class, \App\Models\Order::class,
            \App\Models\Payment::class, \App\Models\Product::class,
            \App\Models\Promotion::class, \App\Modules\Inventario\Models\Proveedor::class,
            \App\Models\Quote::class, \App\Models\RifaVenta::class,
        ];

        foreach ($modelos as $modelo) {
            $sql = $modelo::query()->toSql();
            $this->assertStringContainsString('project_id', $sql,
                "{$modelo} perdió el scope de proyecto: sus consultas ya no filtran por tenant");
        }
    }

    /** TD-001 CERRADA: la política ante ausencia de proyecto ya es fail-closed
     *  para usuarios autenticados. Un gerente sin proyecto en sesión no ve
     *  NADA, en vez de verlo todo (que era el fail-open anterior). */
    public function test_autenticado_sin_proyecto_en_sesion_no_ve_nada(): void
    {
        $this->ventaDe($this->mio);
        $this->ventaDe($this->ajeno);

        $u = User::factory()->create(['is_superadmin' => 0]);
        $this->actingAs($u);
        session()->forget(['active_project_id', 'comercial_project_id']);

        $this->assertSame(0, RifaVenta::count(),
            'Autenticado sin proyecto: cerrado, no abierto');
        $this->assertSame(2, RifaVenta::allProjects()->count(),
            'El escape explícito sigue disponible para el sistema');
    }

    /** El superadmin opera globalmente (bixoadmin) y los visitantes sin
     *  usuario (webhooks del bot, páginas públicas) mantienen el contrato
     *  neutro del que dependen los bots. */
    public function test_superadmin_y_anonimos_mantienen_acceso_neutro(): void
    {
        $this->ventaDe($this->mio);
        session()->forget(['active_project_id', 'comercial_project_id']);

        // Anónimo (así llegan los webhooks): neutro.
        auth()->logout();
        $this->assertSame(1, RifaVenta::count(), 'Sin usuario el scope es neutro');

        // Superadmin sin proyecto: global.
        $this->actingAs(User::factory()->create(['is_superadmin' => 1]));
        $this->assertSame(1, RifaVenta::count(), 'El superadmin no queda cerrado');
    }

    /** El candado nuevo de catálogos: el catálogo propio no puede usarse de
     *  fachada para editar valores de un catálogo ajeno. */
    public function test_un_valor_de_catalogo_ajeno_no_se_edita_con_fachada_propia(): void
    {
        Permission::findOrCreate('settings.catalogos', 'web');
        $catalogoMio   = \App\Models\CatalogList::create(['project_id' => $this->mio->id, 'name' => 'Mío', 'type' => 'custom']);
        $catalogoAjeno = \App\Models\CatalogList::create(['project_id' => $this->ajeno->id, 'name' => 'Ajeno', 'type' => 'custom']);
        $valorAjeno    = $catalogoAjeno->values()->create(['label' => 'Secreto ajeno']);

        $this->entrarComo($this->mio);
        auth()->user()->givePermissionTo('settings.catalogos');
        $this->actingAs(auth()->user()->fresh())->withSession([
            'comercial_project_id' => $this->mio->id,
            'active_project_id'    => $this->mio->id,
        ]);

        $this->put("/catalogs/{$catalogoMio->id}/values/{$valorAjeno->id}", ['label' => 'Hackeado'])
            ->assertNotFound();

        $this->assertSame('Secreto ajeno', $valorAjeno->fresh()->label);
    }

    /** El candado nuevo de bots: una transición de un flujo ajeno no se borra. */
    public function test_una_transicion_de_bot_ajena_no_se_borra(): void
    {
        Permission::findOrCreate('settings.negocio', 'web');
        // Conviven dos generaciones de flujos (TD-010): la FK de bot_states
        // apunta a la tabla vieja `bot_flows`, pero BotState::flow() lee la
        // nueva `bot_builder_flows`. En BD limpia ambos autoincrementos
        // arrancan en 1, así que se crean alineados como en producción.
        $flujoAjeno = \App\Models\BotFlow::create(['project_id' => $this->ajeno->id, 'nombre' => 'Flujo ajeno']);
        \Illuminate\Support\Facades\DB::table('bot_flows')->insert([
            'id' => $flujoAjeno->id, 'project_id' => $this->ajeno->id, 'name' => 'Flujo ajeno',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $estado     = \App\Models\BotState::create(['flow_id' => $flujoAjeno->id, 'key' => 'inicio', 'label' => 'Inicio', 'message' => 'Hola']);
        $transicion = \App\Models\BotTransition::create(['from_state_id' => $estado->id, 'to_state_id' => $estado->id]);

        $this->entrarComo($this->mio);
        auth()->user()->givePermissionTo('settings.negocio');
        $this->actingAs(auth()->user()->fresh())->withSession([
            'comercial_project_id' => $this->mio->id,
            'active_project_id'    => $this->mio->id,
        ]);

        $this->delete('/bots/transitions/'.$transicion->id)->assertNotFound();

        $this->assertNotNull(\App\Models\BotTransition::find($transicion->id),
            'La transición ajena debe seguir existiendo');
    }
}
