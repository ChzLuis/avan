<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F2 v1 — Cuentas por Cobrar (lectura).
 *
 * Contratos: exactitud del dinero en centavos, sin doble conteo de
 * convertidas, aislamiento por proyecto y permiso.
 */
class CxcTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['reports.ver', 'quotes.ver', 'orders.ver', 'settings.pagos'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'CxC QA', 'slug' => 'cxc-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function entrar(array $permisos = ['reports.ver']): User
    {
        $rol = Role::findOrCreate('cxc_' . md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
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

    public function test_sin_permiso_de_reportes_no_entra(): void
    {
        $this->entrar(['quotes.ver', 'orders.ver']);   // sin reports.ver
        $this->get('/bixosales/cuentas')->assertStatus(403);
    }

    public function test_agrega_saldos_exactos_en_centavos(): void
    {
        $this->entrar();
        // Pedido pendiente completo: debe 19,345.50
        Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '19345.50']);
        // Pedido parcial: 1,266.00 con adelanto 266.00 -> debe 1,000.00
        Order::create(['project_id' => $this->project->id, 'client_name' => 'B',
            'status' => 'pending', 'payment_status' => 'partial', 'total' => '1266.00',
            'advance_amount' => '266.00']);
        // Pedido pagado: fuera
        Order::create(['project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'done', 'payment_status' => 'paid', 'total' => '99.99']);

        $r = $this->get('/bixosales/cuentas')->assertSuccessful();
        $resumen = $r->viewData('resumen');
        $filas   = $r->viewData('filas');

        // 19,345.50 + 1,000.00 = 20,345.50 EXACTO (en float, 19345.50+1000
        // podria dar 20345.499999...)
        $this->assertSame('20,345.50', $resumen['total']);
        $this->assertSame(2, $resumen['documentos']);
        $saldos = array_column($filas, 'saldo');
        $this->assertContains('19,345.50', $saldos);
        $this->assertContains('1,000.00', $saldos);
    }

    /**
     * Regresion: NOT IN descarta las filas NULL (logica de tres valores de
     * SQL), asi que un pedido sin payment_status desaparecia del listado en
     * vez de contarse como deuda. En ARIN eran 3 pedidos = S/ 8 298,00 que
     * el negocio no veia. Un nulo significa "nadie ha registrado cobro", que
     * es exactamente deuda.
     */
    public function test_un_pedido_sin_estado_de_pago_sigue_siendo_deuda(): void
    {
        $this->entrar();
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Nulo',
            'status' => 'pending', 'payment_status' => null, 'total' => '8298.00']);

        $r = $this->get('/bixosales/cuentas')->assertSuccessful();
        $this->assertSame(1, $r->viewData('resumen')['documentos'],
            'un pedido con payment_status NULL debe aparecer como deuda');
        $this->assertSame('8,298.00', $r->viewData('resumen')['total']);
    }

    /** Un pedido anulado con la palabra antigua tampoco es deuda. */
    public function test_anulado_legacy_no_cuenta_como_deuda(): void
    {
        $this->entrar();
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Anulado',
            'status' => 'cancelado', 'payment_status' => 'pending', 'total' => '500.00']);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Vivo',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '120.00']);

        $r = $this->get('/bixosales/cuentas')->assertSuccessful();
        $this->assertSame(1, $r->viewData('resumen')['documentos']);
        $this->assertSame('120.00', $r->viewData('resumen')['total']);
    }

    /**
     * F2b: "vencido" pasa a ser la fecha PACTADA, no "lleva mas de 15 dias
     * creado". Un pedido a 30 dias de credito con 20 de antiguedad estaba
     * marcado como moroso y ya no lo esta.
     */
    public function test_el_vencido_sale_del_vencimiento_pactado_no_de_la_antiguedad(): void
    {
        $this->entrar();
        $o = Order::create(['project_id' => $this->project->id, 'client_name' => 'A credito',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '1000.00']);
        $o->forceFill(['created_at' => now()->subDays(20)])->save();

        // Sin vencimiento registrado, la logica vieja lo daria por vencido.
        $r = $this->get('/bixosales/cuentas')->assertSuccessful();
        $this->assertSame('1,000.00', $r->viewData('resumen')['vencido'],
            'sin vencimiento pactado se cae a la antiguedad');

        // Con el vencimiento pactado a 30 dias, aun NO vence.
        \App\Modules\Finanzas\Models\ReceivableTerm::create([
            'project_id' => $this->project->id, 'payable_type' => 'order', 'payable_id' => $o->id,
            'numero' => 1, 'due_date' => now()->addDays(10)->toDateString(), 'amount_cents' => 100000,
        ]);

        $r2 = $this->get('/bixosales/cuentas')->assertSuccessful();
        $this->assertSame('0.00', $r2->viewData('resumen')['vencido'],
            'con 10 dias por delante no puede estar vencido');
        $this->assertSame('1,000.00', $r2->viewData('resumen')['total'],
            'la deuda sigue siendo la misma: cambia el semaforo, no el importe');

        $fila = $r2->viewData('filas')[0];
        $this->assertFalse($fila['vencido']);
        $this->assertFalse($fila['estimado'], 'el vencimiento es pactado, no estimado');
        $this->assertSame(now()->addDays(10)->format('d/m/Y'), $fila['vence']);
    }

    /** Pasada la fecha pactada sí cuenta como vencido, con su atraso real. */
    public function test_pasada_la_fecha_pactada_cuenta_como_vencido(): void
    {
        $this->entrar();
        $o = Order::create(['project_id' => $this->project->id, 'client_name' => 'Moroso',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '500.00']);
        \App\Modules\Finanzas\Models\ReceivableTerm::create([
            'project_id' => $this->project->id, 'payable_type' => 'order', 'payable_id' => $o->id,
            'numero' => 1, 'due_date' => now()->subDays(5)->toDateString(), 'amount_cents' => 50000,
        ]);

        $r = $this->get('/bixosales/cuentas')->assertSuccessful();
        $this->assertSame('500.00', $r->viewData('resumen')['vencido']);
        $this->assertSame(5, $r->viewData('filas')[0]['atraso']);
    }

    /** Ver la deuda y cambiar las condiciones son permisos distintos. */
    public function test_un_lector_no_puede_cambiar_las_condiciones(): void
    {
        $this->entrar(['reports.ver']);   // puede ver, no configurar
        $this->post('/bixosales/cuentas/condiciones', [
            'cxc_plazo_dias' => 30, 'cxc_dias_aviso' => 3,
        ])->assertStatus(403);

        $this->assertNull($this->project->fresh()->setting('cxc_plazo_dias'));
    }

    public function test_con_permiso_se_guarda_el_plazo_y_no_reescribe_lo_pactado(): void
    {
        $this->entrar(['reports.ver', 'settings.pagos']);
        $o = Order::create(['project_id' => $this->project->id, 'client_name' => 'X',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '100.00']);
        $termino = \App\Modules\Finanzas\Models\ReceivableTerm::create([
            'project_id' => $this->project->id, 'payable_type' => 'order', 'payable_id' => $o->id,
            'numero' => 1, 'due_date' => '2026-01-15', 'amount_cents' => 10000,
        ]);

        $this->post('/bixosales/cuentas/condiciones', [
            'cxc_plazo_dias' => 30, 'cxc_dias_aviso' => 5,
        ])->assertRedirect();

        $this->assertSame('30', $this->project->fresh()->setting('cxc_plazo_dias'));
        $this->assertSame('2026-01-15', $termino->fresh()->due_date->toDateString(),
            'un vencimiento ya pactado es un hecho: no se reescribe al cambiar la preferencia');
    }

    /** Recalcular es opt-in y explicito. */
    public function test_marcando_recalcular_si_se_actualizan_los_pendientes(): void
    {
        $this->entrar(['reports.ver', 'settings.pagos']);
        $o = Order::create(['project_id' => $this->project->id, 'client_name' => 'X',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '100.00']);
        $o->forceFill(['created_at' => '2026-08-01 10:00:00'])->save();
        $termino = \App\Modules\Finanzas\Models\ReceivableTerm::create([
            'project_id' => $this->project->id, 'payable_type' => 'order', 'payable_id' => $o->id,
            'numero' => 1, 'due_date' => '2026-08-01', 'amount_cents' => 10000,
        ]);

        $this->post('/bixosales/cuentas/condiciones', [
            'cxc_plazo_dias' => 30, 'cxc_dias_aviso' => 3, 'recalcular' => 1,
        ])->assertRedirect();

        $this->assertSame('2026-08-31', $termino->fresh()->due_date->toDateString());
    }

    public function test_el_plazo_se_valida(): void
    {
        $this->entrar(['reports.ver', 'settings.pagos']);
        $this->post('/bixosales/cuentas/condiciones', [
            'cxc_plazo_dias' => -5, 'cxc_dias_aviso' => 3,
        ])->assertSessionHasErrors('cxc_plazo_dias');
    }

    public function test_la_cartera_es_solo_de_ventas_ninguna_cotizacion_entra(): void
    {
        $this->entrar();

        // Convertida + su pedido: entra el pedido, que es la venta.
        $q = Quote::create(['project_id' => $this->project->id, 'client_name' => 'Conv',
            'status' => 'converted', 'payment_status' => 'pending', 'total' => '500.00']);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Conv',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '500.00',
            'quote_id' => $q->id]);
        // Aceptada sin convertir: tampoco. Es un presupuesto aceptado, no una
        // venta: no reconoce ingreso ni genera obligacion de pago.
        Quote::create(['project_id' => $this->project->id, 'client_name' => 'Acc',
            'status' => 'accepted', 'payment_status' => 'pending', 'total' => '300.00']);
        Quote::create(['project_id' => $this->project->id, 'client_name' => 'Env',
            'status' => 'sent', 'payment_status' => 'pending', 'total' => '999.00']);

        $r = $this->get('/bixosales/cuentas')->assertSuccessful();
        $resumen = $r->viewData('resumen');

        $this->assertSame('500.00', $resumen['total']);
        $this->assertSame(1, $resumen['documentos']);
        foreach ($r->viewData('filas') as $f) {
            $this->assertSame('pedido', $f['tipo'], 'la cartera solo admite ventas');
        }
    }

    public function test_lo_aceptado_sin_convertir_es_trabajo_pendiente_no_deuda(): void
    {
        // Antes esta cotizacion aportaba S/ 10,000 al "por cobrar". El cliente
        // acepto un presupuesto y adelanto dinero: no debe nada, y el adelanto
        // es un pasivo (anticipo), no un activo. Ahora vive como accion.
        Quote::create(['project_id' => $this->project->id, 'client_name' => 'P',
            'status' => 'accepted', 'payment_status' => 'partial',
            'total' => '13420.10', 'paid_amount' => '3420.10']);

        $this->entrar();
        $r = $this->get('/bixosales/cuentas')->assertSuccessful();

        $this->assertSame('0.00', $r->viewData('resumen')['total']);

        $pendiente = \App\Support\Cobranza::aceptadasSinConvertir($this->project);
        $this->assertSame(1, $pendiente['n']);
        $this->assertSame(1342010, $pendiente['cents']);
    }

    public function test_no_mezcla_proyectos(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'cxc-ajeno', 'category' => 'retail', 'is_active' => true,
        ]);
        Order::create(['project_id' => $otro->id, 'client_name' => 'X',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '77777.77']);

        $this->entrar();
        $r = $this->get('/bixosales/cuentas')->assertSuccessful();

        $this->assertSame('0.00', $r->viewData('resumen')['total']);
        $this->assertSame(0, $r->viewData('resumen')['documentos']);
    }

    /**
     * El modulo sigue sin mutar deuda: la unica escritura que existe bajo
     * /cuentas es la CONFIGURACION de las condiciones de cobro, y va con su
     * propio permiso. Ningun importe, estado ni saldo se toca desde aqui.
     */
    public function test_no_expone_ninguna_mutacion_de_la_deuda(): void
    {
        $rutas = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($r) => str_contains($r->uri(), 'bixosales/cuentas'));

        $this->assertCount(2, $rutas, 'solo el listado y la configuracion');

        $listado = $rutas->firstWhere(fn ($r) => $r->uri() === 'bixosales/cuentas');
        $this->assertSame(['GET', 'HEAD'], $listado->methods());

        $config = $rutas->firstWhere(fn ($r) => str_ends_with($r->uri(), '/condiciones'));
        $this->assertSame(['POST'], $config->methods());
        $this->assertStringContainsString('settings.pagos', implode(',', $config->gatherMiddleware()),
            'configurar exige su propio permiso, no basta con poder ver');
    }
}
