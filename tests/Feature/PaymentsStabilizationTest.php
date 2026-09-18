<?php

namespace Tests\Feature;

use App\Modules\Bots\Controllers\BotWebhookController;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Estabilizacion de pagos (hotfix autorizado por Codex 2026-08-15).
 *
 * Propiedad protegida: aprobar o rechazar un PAGO nunca toca el estado
 * COMERCIAL del pedido, y todos los escritores emiten vocabulario canonico
 * (paid / pending / under_review). Antes, PagoController@aprobar escribia
 * status='pagado' —el origen activo de los pedidos legacy 20-22— y el bot y
 * la extension creaban pedidos con estados en español.
 */
class PaymentsStabilizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['payments.ver', 'payments.aprobar', 'payments.rechazar', 'orders.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Pagos QA', 'slug' => 'pagos-qa', 'category' => 'retail',
            'is_active' => true,
        ]);
        // copilot_token no esta en $fillable de Project: forceFill a proposito.
        $this->project->forceFill(['copilot_token' => 'token-qa-123'])->save();
    }

    private function aprobador(): User
    {
        Role::findOrCreate('aprobador_qa', 'web')
            ->syncPermissions(['payments.ver', 'payments.aprobar', 'payments.rechazar']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Aprobador', 'spatie_role' => 'aprobador_qa', 'is_active' => 1,
        ]);
        $user->syncRoles(['aprobador_qa']);

        $this->actingAs($user)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);

        return $user;
    }

    private function pedidoEnRevision(string $estadoPago = 'under_review'): Order
    {
        return Order::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente Bot',
            'status' => 'pending', 'payment_status' => $estadoPago, 'total' => 150,
        ]);
    }

    // ── Aprobar / rechazar: el estado comercial es intocable ──────────────

    public function test_aprobar_escribe_paid_y_no_toca_el_estado_comercial(): void
    {
        $this->aprobador();
        $order = $this->pedidoEnRevision();

        $r = $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $order->id]);

        $r->assertSuccessful();
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('pending', $order->status, 'aprobar un pago NO completa la venta');
    }

    public function test_rechazar_escribe_pending_y_no_toca_el_estado_comercial(): void
    {
        $this->aprobador();
        $order = $this->pedidoEnRevision();

        $this->postJson('/bixosales/pagos/rechazar', [
            'order_id' => $order->id, 'motivo' => 'Comprobante ilegible',
        ])->assertSuccessful();

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
    }

    public function test_ambos_registran_evento_estructurado(): void
    {
        $this->aprobador();
        $a = $this->pedidoEnRevision();
        $b = $this->pedidoEnRevision();

        $this->postJson('/bixosales/pagos/aprobar',  ['order_id' => $a->id]);
        $this->postJson('/bixosales/pagos/rechazar', ['order_id' => $b->id, 'motivo' => 'Monto no coincide']);

        $evA = OrderEvent::where('order_id', $a->id)->where('action', 'payment_status')->first();
        $this->assertNotNull($evA, 'aprobar debe dejar OrderEvent, no solo texto en notes');
        $this->assertSame('under_review', $evA->meta['from']);
        $this->assertSame('paid', $evA->meta['to']);
        $this->assertSame('aprobacion_bot', $evA->meta['source']);

        $evB = OrderEvent::where('order_id', $b->id)->where('action', 'payment_status')->first();
        $this->assertSame('pending', $evB->meta['to']);
        $this->assertSame('Monto no coincide', $evB->meta['motivo']);
    }

    public function test_el_json_publico_conserva_su_forma(): void
    {
        $this->aprobador();
        $order = $this->pedidoEnRevision();

        $this->postJson('/bixosales/pagos/aprobar', ['order_id' => $order->id])
            ->assertJsonStructure(['ok', 'pedido', 'mensaje_cliente'])
            ->assertJson(['ok' => true, 'pedido' => $order->id]);
    }

    public function test_pendientes_encuentra_el_canonico_y_el_alias_legacy(): void
    {
        $this->aprobador();
        $nuevo  = $this->pedidoEnRevision('under_review');
        $viejo  = $this->pedidoEnRevision('en_revision');   // dato pre-hotfix
        $this->pedidoEnRevision('pending');                 // no debe aparecer

        $r = $this->postJson('/bixosales/pagos/pendientes')->assertSuccessful();
        $ids = collect($r->json()['pendientes'] ?? $r->json())->flatten(1)->pluck('id')->all();

        $this->assertContains($nuevo->id, $ids);
        $this->assertContains($viejo->id, $ids);
        $this->assertCount(2, $ids);
    }

    // ── Escritores: bot y extension emiten canonico ───────────────────────

    public function test_el_bot_crea_pending_y_under_review_para_pago_digital(): void
    {
        $metodo = new \ReflectionMethod(BotWebhookController::class, 'aplicarAcciones');
        $metodo->invoke(new BotWebhookController(), $this->project, '51999000111', 'Cliente WA', [
            'pedido' => [
                'items' => [['nombre' => 'Producto bot', 'precio' => 50, 'cantidad' => 2]],
                'total' => 100,
                'pago'  => 'yape',
            ],
        ]);

        $order = Order::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertSame('under_review', $order->payment_status);
    }

    public function test_la_extension_pagada_crea_pending_paid(): void
    {
        $r = $this->postJson('/api/copilot/venta/pedido', [
            'nombre' => 'Cliente Ext', 'telefono' => '51999000222',
            'items' => [['nombre' => 'Servicio', 'precio' => 80, 'cantidad' => 1]],
            'pagado' => true,
        ], ['X-Copilot-Token' => 'token-qa-123']);

        $r->assertSuccessful();
        $order = Order::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertSame('pending', $order->status, 'pagado = cobro confirmado, no venta culminada');
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_la_extension_no_pagada_crea_pending_pending(): void
    {
        $this->postJson('/api/copilot/venta/pedido', [
            'nombre' => 'Cliente Ext 2', 'telefono' => '51999000333',
            'items' => [['nombre' => 'Servicio', 'precio' => 80, 'cantidad' => 1]],
            'pagado' => false,
        ], ['X-Copilot-Token' => 'token-qa-123'])->assertSuccessful();

        $order = Order::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    // ── OrderStatus reconoce la revision ──────────────────────────────────

    public function test_order_status_normaliza_y_presenta_la_revision(): void
    {
        $this->assertSame('under_review', \App\Support\OrderStatus::pago('en_revision'));
        $this->assertSame('under_review', \App\Support\OrderStatus::pago('under_review'));

        $pill = \App\Support\OrderStatus::pagoPresentacion('en_revision');
        $this->assertSame('En revisión', $pill['label']);

        $this->assertArrayHasKey('under_review', \App\Support\OrderStatus::opcionesPago());
        $this->assertTrue(\App\Support\OrderStatus::debe('pending', 'under_review'),
            'un pago en revision sigue siendo deuda');
    }
}
