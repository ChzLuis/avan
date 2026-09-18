<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CONTRATO de la API de pagos del bot y la extension.
 *
 * Escrito ANTES de tocar `Api\PagoController` (F3b) y contra su comportamiento
 * actual: si al meter el libro de cobros cambia una sola clave o una sola
 * palabra del mensaje, esto falla.
 *
 * `mensaje_cliente` no es un detalle interno: es **el texto que el cliente
 * recibe por WhatsApp**. Cambiarlo sin querer se lo manda a personas reales.
 */
class PagoBotContratoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['payments.ver', 'payments.aprobar', 'payments.rechazar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Bot QA', 'slug' => 'bot-pagos-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'orders'], ['name' => 'orders', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('bot_pagos', 'web');
        $rol->syncPermissions(['payments.ver', 'payments.aprobar', 'payments.rechazar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'editor']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Op', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    private function pedido(string $estadoPago = 'under_review', string $total = '250.00'): Order
    {
        return Order::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente Bot',
            'client_phone' => '987654321', 'status' => 'pending',
            'payment_status' => $estadoPago, 'total' => $total, 'payment_method' => 'Yape',
        ]);
    }

    /** La forma del listado: el bot y la extension la consumen tal cual. */
    public function test_pendientes_devuelve_exactamente_estas_claves(): void
    {
        $this->pedido();

        $r = $this->postJson('/bixosales/pagos/pendientes', [])->assertSuccessful();

        $this->assertSame(['pedidos'], array_keys($r->json()));
        $this->assertSame(
            ['id', 'cliente', 'telefono', 'total', 'metodo', 'notas', 'fecha'],
            array_keys($r->json('pedidos.0')),
            'la extension pinta estas claves: cambiarlas la rompe'
        );
        // JSON serializa 250.00 como 250, asi que el tipo exacto no es estable;
        // lo que el consumidor necesita es que sea NUMERO y no cadena.
        $total = $r->json('pedidos.0.total');
        $this->assertIsNumeric($total);
        $this->assertIsNotString($total, 'total debe viajar como numero JSON, no entrecomillado');
        $this->assertEquals(250.0, $total);
    }

    /** El alias legacy sigue entrando: un pedido en vuelo no puede perderse. */
    public function test_pendientes_acepta_el_alias_legacy_en_revision(): void
    {
        $this->pedido('en_revision');
        $this->pedido('under_review');

        $r = $this->postJson('/bixosales/pagos/pendientes', [])->assertSuccessful();
        $this->assertCount(2, $r->json('pedidos'));
    }

    public function test_pendientes_filtra_por_telefono(): void
    {
        $this->pedido();
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Otro',
            'client_phone' => '111222333', 'status' => 'pending',
            'payment_status' => 'under_review', 'total' => '99.00']);

        $r = $this->postJson('/bixosales/pagos/pendientes', ['telefono' => '987-654-321']);
        $this->assertCount(1, $r->json('pedidos'));
        $this->assertSame('987654321', $r->json('pedidos.0.telefono'));
    }

    /** El texto que recibe el cliente, palabra por palabra. */
    public function test_aprobar_devuelve_el_mensaje_exacto_para_el_cliente(): void
    {
        $o = $this->pedido();

        $r = $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $o->id])->assertSuccessful();

        $this->assertSame(['ok', 'pedido', 'mensaje_cliente'], array_keys($r->json()));
        $this->assertTrue($r->json('ok'));
        $this->assertSame($o->id, $r->json('pedido'));
        $this->assertSame(
            "✅ ¡Confirmamos tu pago del pedido #{$o->id}! 🎉\nYa estamos preparando tu entrega. ¡Gracias por tu compra!",
            $r->json('mensaje_cliente')
        );
    }

    public function test_rechazar_devuelve_el_mensaje_exacto_con_su_motivo(): void
    {
        $o = $this->pedido();

        $r = $this->postJson('/bixosales/pagos/rechazar', [
            'order_id' => $o->id, 'motivo' => 'El voucher no se lee',
        ])->assertSuccessful();

        $this->assertSame(['ok', 'pedido', 'mensaje_cliente'], array_keys($r->json()));
        $this->assertSame(
            "⚠️ Sobre tu pedido #{$o->id}: El voucher no se lee.\n¿Puedes reenviarnos el comprobante o escribirnos para ayudarte?",
            $r->json('mensaje_cliente')
        );
    }

    public function test_rechazar_sin_motivo_usa_el_texto_por_defecto(): void
    {
        $o = $this->pedido();

        $r = $this->postJson('/bixosales/pagos/rechazar', ['order_id' => $o->id])->assertSuccessful();

        $this->assertStringContainsString('No pudimos validar el comprobante', $r->json('mensaje_cliente'));
    }

    /** Aprobar cobra, pero NO cierra la venta: el estado comercial no se toca. */
    public function test_aprobar_no_toca_el_estado_comercial(): void
    {
        $o = $this->pedido();

        $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $o->id])->assertSuccessful();

        $o->refresh();
        $this->assertSame('paid', $o->payment_status);
        $this->assertSame('pending', $o->status, 'aprobar un pago no completa la venta');
        $this->assertStringContainsString('Pago APROBADO', $o->notes);
    }

    public function test_rechazar_deja_el_pago_pendiente_y_anota_el_motivo(): void
    {
        $o = $this->pedido();

        $this->postJson('/bixosales/pagos/rechazar', ['order_id' => $o->id, 'motivo' => 'Monto no coincide'])
            ->assertSuccessful();

        $o->refresh();
        $this->assertSame('pending', $o->payment_status);
        $this->assertStringContainsString('Monto no coincide', $o->notes);
    }

    // ── F3b: por dentro cambia, por fuera no ────────────────────────────

    /** La aprobacion del bot deja un asiento, no una mutacion. */
    public function test_aprobar_registra_el_cobro_en_el_libro(): void
    {
        $o = $this->pedido('under_review', '250.00');

        $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $o->id])->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'payable_type' => 'order', 'payable_id' => $o->id,
            'amount_cents' => 25000, 'source' => 'bot',
        ]);
        $this->assertSame('250.00', (string) $o->fresh()->advance_amount);
    }

    /**
     * El bot reintenta cuando se le corta la red. Aprobar dos veces no puede
     * duplicar el cobro ni devolverle un error por algo que ya hizo bien.
     */
    public function test_aprobar_dos_veces_no_duplica_el_cobro(): void
    {
        $o = $this->pedido('under_review', '250.00');

        $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $o->id])->assertSuccessful();
        $r = $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $o->id])->assertSuccessful();

        $this->assertSame(1, \App\Modules\Finanzas\Models\Payment::where('payable_id', $o->id)->count());
        $this->assertSame(25000, \App\Modules\Finanzas\Support\Ledger::cobradoCents($this->project->id, 'order', $o->id));
        $this->assertTrue($r->json('ok'), 'un reintento debe seguir respondiendo ok');
    }

    /** Rechazar tras haber aprobado revierte, y el rastro queda. */
    public function test_rechazar_despues_de_aprobar_revierte_el_asiento(): void
    {
        $o = $this->pedido('under_review', '250.00');
        $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $o->id])->assertSuccessful();

        $this->postJson('/bixosales/pagos/rechazar', [
            'order_id' => $o->id, 'motivo' => 'El comprobante era de otra persona',
        ])->assertSuccessful();

        $this->assertSame(2, \App\Modules\Finanzas\Models\Payment::where('payable_id', $o->id)->count(),
            'el asiento original sigue, mas su reversion');
        $this->assertSame(0, \App\Modules\Finanzas\Support\Ledger::cobradoCents($this->project->id, 'order', $o->id));
        $this->assertSame('pending', $o->fresh()->payment_status);
        $this->assertDatabaseHas('payments', ['reversal_reason' => 'El comprobante era de otra persona']);
    }

    /** Un pedido de otro proyecto nunca se aprueba. */
    public function test_no_aprueba_pedidos_de_otro_proyecto(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-bot', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajeno = Order::create(['project_id' => $otro->id, 'client_name' => 'X',
            'status' => 'pending', 'payment_status' => 'under_review', 'total' => '10.00']);

        $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $ajeno->id])->assertStatus(404);
        $this->assertSame('under_review', $ajeno->fresh()->payment_status);
    }
}
