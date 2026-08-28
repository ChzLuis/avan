<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cierre de RISK-008: el conector de WhatsApp autentica con un secreto POR
 * PROYECTO (`wa_bot_token`) y toda operación sobre un pedido valida que el
 * token pertenece al DUEÑO del pedido. El conector de la Empresa A no puede
 * leer ni mutar pedidos de la Empresa B, aunque conozca el order_id.
 */
class WaBotIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Project $empresaA;
    private Project $empresaB;
    private Order $pedidoB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresaA = $this->negocioConToken('emp-a', 'wabot_'.Str::random(48));
        $this->empresaB = $this->negocioConToken('emp-b', 'wabot_'.Str::random(48));

        $this->pedidoB = $this->empresaB->orders()->create([
            'client_name' => 'Cliente de B', 'client_phone' => '999000111',
            'wa_number' => '999000111', 'status' => 'pending', 'wa_status' => 'pending',
            'total' => 100, 'sales_channel' => 'whatsapp',
        ]);
    }

    private function negocioConToken(string $slug, string $token): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => strtoupper($slug), 'slug' => $slug, 'category' => 'retail',
            'is_active' => true, 'wa_phone' => '51'.random_int(900000000, 999999999),
        ]);
        $p->forceFill(['wa_bot_token' => $token])->saveQuietly();
        return $p->fresh();
    }

    public function test_el_token_de_A_no_confirma_el_pago_de_un_pedido_de_B(): void
    {
        $this->postJson("/wa/order/{$this->pedidoB->id}/payment", [
            'token' => $this->empresaA->wa_bot_token,
        ])->assertForbidden();

        $this->assertSame('pending', $this->pedidoB->fresh()->wa_status);
    }

    public function test_el_token_de_A_no_altera_el_total_ni_la_direccion_de_B(): void
    {
        $this->postJson("/wa/order/{$this->pedidoB->id}/delivery", [
            'token' => $this->empresaA->wa_bot_token,
            'delivery_address' => 'Calle del atacante',
            'shipping_cost' => 9999,
        ])->assertForbidden();

        $fresco = $this->pedidoB->fresh();
        $this->assertEqualsWithDelta(100, (float) $fresco->total, 0.001);
        $this->assertNull($fresco->delivery_address);
    }

    public function test_el_token_de_A_no_confirma_entrega_de_B(): void
    {
        $this->postJson("/wa/order/{$this->pedidoB->id}/confirmed", [
            'token' => $this->empresaA->wa_bot_token, 'confirmed' => true,
        ])->assertForbidden();
        $this->assertSame('pending', $this->pedidoB->fresh()->status);
    }

    public function test_find_order_solo_ve_pedidos_del_tenant_del_token(): void
    {
        // A busca por el teléfono que compró en B → no debe encontrar el pedido de B.
        $this->postJson('/wa/find-order', [
            'token' => $this->empresaA->wa_bot_token, 'wa_number' => '999000111',
        ])->assertOk()->assertJson(['ok' => false]);

        // El propio dueño (B) sí lo encuentra.
        $this->postJson('/wa/find-order', [
            'token' => $this->empresaB->wa_bot_token, 'wa_number' => '999000111',
        ])->assertOk()->assertJson(['ok' => true, 'order_id' => $this->pedidoB->id]);
    }

    public function test_el_dueno_si_opera_su_propio_pedido(): void
    {
        $this->postJson("/wa/order/{$this->pedidoB->id}/payment", [
            'token' => $this->empresaB->wa_bot_token,
        ])->assertOk();
        $this->assertSame('pago_recibido', $this->pedidoB->fresh()->wa_status);
    }

    public function test_un_token_invalido_es_401(): void
    {
        $this->postJson("/wa/order/{$this->pedidoB->id}/payment", [
            'token' => 'token-que-no-existe',
        ])->assertUnauthorized();
    }

    public function test_el_token_global_legacy_sigue_operando_durante_la_transicion(): void
    {
        // Puente de compatibilidad: mientras el bot migra a wa_bot_token, el
        // token global (ya fuera del repo, en config) sigue autorizando.
        config(['services.wabot.token' => 'token-global-de-transicion']);

        $this->postJson("/wa/order/{$this->pedidoB->id}/payment", [
            'token' => 'token-global-de-transicion',
        ])->assertOk();
        $this->assertSame('pago_recibido', $this->pedidoB->fresh()->wa_status);
    }
}
