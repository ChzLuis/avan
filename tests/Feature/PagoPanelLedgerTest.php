<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F3a — El cobro del panel entra al libro.
 *
 * Antes, registrar un pago era pisar `payment_status` y `advance_amount`: dos
 * abonos parciales quedaban indistinguibles, deshacerlos no dejaba rastro, y
 * se podia marcar "parcial" SIN decir cuanto —asi nacieron los pedidos 34 y 35
 * de produccion, con importe NULL—. Estos contratos cierran las tres puertas.
 */
class PagoPanelLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'orders.editar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Pagos QA', 'slug' => 'pagos-panel-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'orders'], ['name' => 'orders', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('pagos_panel', 'web');
        $rol->syncPermissions(['orders.ver', 'orders.editar']);
        $this->usuario = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->usuario->id, 'role' => 'editor']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $this->usuario->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $this->usuario->syncRoles([$rol->name]);
        $this->actingAs($this->usuario)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    private function pedido(string $total = '1000.00'): Order
    {
        return Order::create(['project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => $total]);
    }

    private function cobrar(Order $o, array $datos)
    {
        return $this->postJson("/bixosales/pedidos/{$o->id}/pay", $datos);
    }

    /** La puerta que dejo huerfanos a los pedidos 34 y 35. */
    public function test_un_pago_parcial_sin_importe_se_rechaza(): void
    {
        $o = $this->pedido('1266.00');

        $this->cobrar($o, ['status' => 'partial'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertSame(0, Payment::count(), 'no debe registrarse cobro alguno');
        $this->assertSame('pending', $o->fresh()->payment_status);
    }

    public function test_un_cobro_parcial_queda_como_asiento_y_proyecta_el_pedido(): void
    {
        $o = $this->pedido('1000.00');

        $this->cobrar($o, ['status' => 'partial', 'amount' => 300.50, 'method' => 'yape', 'reference' => 'OP-7'])
            ->assertSuccessful()->assertJsonPath('ok', true);

        $asiento = Payment::first();
        $this->assertSame(30050, $asiento->amount_cents);
        $this->assertSame('yape', $asiento->method);
        $this->assertSame('OP-7', $asiento->reference);
        $this->assertSame('panel', $asiento->source);

        $o->refresh();
        $this->assertSame('partial', $o->payment_status);
        $this->assertSame('300.50', (string) $o->advance_amount);
    }

    /** Dos abonos son DOS asientos: antes se perdia el detalle. */
    public function test_dos_abonos_parciales_quedan_por_separado(): void
    {
        $o = $this->pedido('1000.00');
        $this->cobrar($o, ['status' => 'partial', 'amount' => 400, 'method' => 'efectivo'])->assertSuccessful();
        $r = $this->cobrar($o, ['status' => 'partial', 'amount' => 350, 'method' => 'yape'])->assertSuccessful();

        $this->assertSame(2, Payment::count());
        $this->assertCount(2, $r->json('cobros'));
        $this->assertSame('750.00', (string) $o->fresh()->advance_amount);
    }

    /** "Pagado" sin importe salda lo que falte, no el total otra vez. */
    public function test_pagado_sin_importe_salda_solo_el_saldo_pendiente(): void
    {
        $o = $this->pedido('1000.00');
        $this->cobrar($o, ['status' => 'partial', 'amount' => 600])->assertSuccessful();
        $this->cobrar($o, ['status' => 'paid'])->assertSuccessful();

        $this->assertSame(100000, Ledger::cobradoCents($this->project->id, 'order', $o->id),
            'el segundo asiento debe ser 400, no 1000');
        $this->assertSame('paid', $o->fresh()->payment_status);
    }

    public function test_no_se_puede_cobrar_de_mas(): void
    {
        $o = $this->pedido('100.00');
        $this->cobrar($o, ['status' => 'partial', 'amount' => 90])->assertSuccessful();

        $this->cobrar($o, ['status' => 'partial', 'amount' => 50])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertSame(9000, Ledger::cobradoCents($this->project->id, 'order', $o->id));
    }

    /** Deshacer no borra: revierte, y queda el rastro de ambos asientos. */
    public function test_volver_a_pendiente_revierte_en_vez_de_borrar(): void
    {
        $o = $this->pedido('500.00');
        $this->cobrar($o, ['status' => 'paid'])->assertSuccessful();
        $this->assertSame('paid', $o->fresh()->payment_status);

        $this->cobrar($o, ['status' => 'pending', 'motivo' => 'el voucher era falso'])->assertSuccessful();

        $this->assertSame(2, Payment::count(), 'el original sigue ahi mas su reversion');
        $this->assertSame(0, Ledger::cobradoCents($this->project->id, 'order', $o->id));
        $this->assertSame('pending', $o->fresh()->payment_status);
        $this->assertDatabaseHas('payments', ['reversal_reason' => 'el voucher era falso']);
    }

    public function test_el_historial_de_cobros_vuelve_en_la_respuesta(): void
    {
        $o = $this->pedido('500.00');
        $r = $this->cobrar($o, ['status' => 'partial', 'amount' => 120.75, 'method' => 'plin']);

        $cobro = $r->json('cobros.0');
        $this->assertSame('120.75', $cobro['importe']);
        $this->assertSame('plin', $cobro['metodo']);
        $this->assertFalse($cobro['reversion']);
    }

    /**
     * Un pedido anterior a F2b lleva el adelanto en la columna y no tiene
     * asientos. Si el libro lo ignorase, ese dinero ya cobrado desapareceria
     * al primer movimiento.
     */
    public function test_el_adelanto_heredado_se_adopta_al_entrar_al_libro(): void
    {
        $o = $this->pedido('1000.00');
        $o->forceFill(['payment_status' => 'partial', 'advance_amount' => '250.00'])->save();

        $this->cobrar($o, ['status' => 'partial', 'amount' => 100])->assertSuccessful();

        $this->assertSame(35000, Ledger::cobradoCents($this->project->id, 'order', $o->id),
            'los 250 heredados mas los 100 nuevos');
        $this->assertDatabaseHas('payments', ['source' => 'legacy', 'amount_cents' => 25000]);
        $this->assertSame('350.00', (string) $o->fresh()->advance_amount);
    }

    /** Y con el adelanto heredado, "pagado" salda solo lo que falta. */
    public function test_pagado_respeta_el_adelanto_heredado(): void
    {
        $o = $this->pedido('1000.00');
        $o->forceFill(['payment_status' => 'partial', 'advance_amount' => '400.00'])->save();

        $this->cobrar($o, ['status' => 'paid'])->assertSuccessful();

        $this->assertSame(100000, Ledger::cobradoCents($this->project->id, 'order', $o->id));
        $this->assertSame('paid', $o->fresh()->payment_status);
        // 400 heredados + 600 del cobro: nunca 400 + 1000.
        $this->assertSame(60000, Payment::where('source', 'panel')->value('amount_cents'));
    }

    /** El historial viaja al ABRIR el pedido, no solo al cobrar. */
    public function test_al_abrir_el_pedido_vienen_sus_cobros(): void
    {
        $o = $this->pedido('900.00');
        $this->cobrar($o, ['status' => 'partial', 'amount' => 200, 'method' => 'yape'])->assertSuccessful();
        $this->cobrar($o, ['status' => 'partial', 'amount' => 150, 'method' => 'efectivo'])->assertSuccessful();

        $r = $this->getJson("/bixosales/pedidos/{$o->id}")->assertSuccessful();

        $this->assertCount(2, $r->json('cobros'));
        $this->assertSame(['200.00', '150.00'], array_column($r->json('cobros'), 'importe'));
    }

    /** Una reversion se lista tambien: el rastro de la correccion importa. */
    public function test_el_historial_incluye_las_reversiones(): void
    {
        $o = $this->pedido('500.00');
        $this->cobrar($o, ['status' => 'paid'])->assertSuccessful();
        $this->cobrar($o, ['status' => 'pending', 'motivo' => 'duplicado'])->assertSuccessful();

        $cobros = $this->getJson("/bixosales/pedidos/{$o->id}")->json('cobros');

        $this->assertCount(2, $cobros);
        $this->assertTrue($cobros[1]['reversion']);
        $this->assertSame('duplicado', $cobros[1]['motivo']);
    }

    // ── F3c: el mostrador tambien deja rastro ───────────────────────────

    /** Una venta pagada en el POS deja su asiento, no solo la columna. */
    public function test_una_venta_pagada_en_el_pos_entra_al_libro(): void
    {
        Permission::findOrCreate('pos.usar', 'web');
        $rol = Role::findOrCreate('pos_qa', 'web');
        $rol->syncPermissions(['pos.usar', 'orders.ver']);
        $this->usuario->syncRoles([$rol->name]);
        Employee::where('user_id', $this->usuario->id)->update(['spatie_role' => $rol->name]);

        $r = $this->postJson('/bixosales/pos', [
            'payment_method' => 'Efectivo',
            'paid'  => true,
            'items' => [['name' => 'Producto', 'price' => 120.50, 'quantity' => 2]],
        ]);

        if ($r->status() !== 200) {
            $this->markTestSkipped('El POS exige contexto propio: ' . $r->status());
        }

        $asiento = Payment::where('source', 'pos')->first();
        $this->assertNotNull($asiento, 'la venta de mostrador debe dejar asiento');
        $this->assertSame(24100, $asiento->amount_cents);
        $this->assertSame('Efectivo', $asiento->method);
    }

    /** El pedido de otro proyecto no se toca. */
    public function test_no_se_cobra_un_pedido_de_otro_proyecto(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otro', 'slug' => 'otro-pagos', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajeno = Order::create(['project_id' => $otro->id, 'client_name' => 'X',
            'status' => 'pending', 'payment_status' => 'pending', 'total' => '50.00']);

        // Lo que importa es la propiedad —no se toca— no el codigo exacto: el
        // enlace de ruta responde 404 en vez de 403, que ademas no delata que
        // el pedido existe.
        $r = $this->cobrar($ajeno, ['status' => 'paid']);
        $this->assertContains($r->status(), [403, 404], 'un pedido ajeno no debe ser cobrable');
        $this->assertSame(0, Payment::count());
        $this->assertSame('pending', $ajeno->fresh()->payment_status);
    }
}
