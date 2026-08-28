<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\RifaVenta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cierre completo de RISK-009: toda la capacidad Rifa aísla por tenant.
 * (a) Rutas públicas /wa/rifa* — secreto por proyecto + ownership.
 * (b) Panel — el filtro de BotInstance por proyecto y el scope de RifaVenta
 *     impiden tocar ventas de otro negocio.
 */
class RifaIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Project $empresaA;
    private Project $empresaB;
    private RifaVenta $ventaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresaA = $this->negocio('rifa-a', 'wabot_'.Str::random(48));
        $this->empresaB = $this->negocio('rifa-b', 'wabot_'.Str::random(48));

        $this->ventaB = RifaVenta::create([
            'project_id' => $this->empresaB->id,
            'order_number' => RifaVenta::generateOrderNumber(),
            'wa_number' => '999222333', 'plan' => 'bot', 'plan_nombre' => 'Plan B',
            'tickets' => 1, 'monto' => 20, 'nombre' => 'Cliente B', 'status' => 'pendiente',
        ]);
    }

    private function negocio(string $slug, string $token): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => strtoupper($slug), 'slug' => $slug, 'category' => 'retail', 'is_active' => true,
        ]);
        $p->forceFill(['wa_bot_token' => $token])->saveQuietly();
        return $p->fresh();
    }

    // ── (a) Rutas públicas del conector ──────────────────────────────

    public function test_wa_rifa_data_con_token_de_A_no_muta_venta_de_B(): void
    {
        $this->postJson("/wa/rifa/{$this->ventaB->id}/data", [
            'token' => $this->empresaA->wa_bot_token, 'nombre' => 'Hackeado',
        ])->assertForbidden();

        $this->assertSame('Cliente B', $this->ventaB->fresh()->nombre);
    }

    public function test_wa_rifa_data_sin_token_es_401(): void
    {
        $this->postJson("/wa/rifa/{$this->ventaB->id}/data", ['nombre' => 'X'])
            ->assertUnauthorized();
    }

    public function test_wa_rifa_payment_proof_con_token_de_A_no_toca_venta_de_B(): void
    {
        $this->postJson("/wa/rifa/{$this->ventaB->id}/payment-proof", [
            'token' => $this->empresaA->wa_bot_token, 'image_base64' => base64_encode('x'),
        ])->assertForbidden();

        $this->assertSame('pendiente', $this->ventaB->fresh()->status);
    }

    public function test_el_dueno_si_actualiza_su_venta_de_rifa(): void
    {
        $this->postJson("/wa/rifa/{$this->ventaB->id}/data", [
            'token' => $this->empresaB->wa_bot_token, 'nombre' => 'Cliente B Actualizado',
        ])->assertOk();

        $this->assertSame('Cliente B Actualizado', $this->ventaB->fresh()->nombre);
    }

    // ── (b) Panel: el scope de RifaVenta aísla las mutaciones ────────

    public function test_confirmar_una_venta_ajena_desde_el_panel_da_404(): void
    {
        // Un usuario del negocio A, con su sesión, no puede confirmar la venta de B.
        $this->actuarComo($this->empresaA);

        $this->post("/rifas/{$this->ventaB->id}/confirmar")->assertNotFound();
        $this->assertSame('pendiente', $this->ventaB->fresh()->status);
    }

    public function test_cancelar_una_venta_ajena_desde_el_panel_da_404(): void
    {
        $this->actuarComo($this->empresaA);

        $this->post("/rifas/{$this->ventaB->id}/cancelar")->assertNotFound();
        $this->assertSame('pendiente', $this->ventaB->fresh()->status);
    }

    /** Autentica un usuario del proyecto dado con permisos de rifa. */
    private function actuarComo(Project $project): void
    {
        foreach (['rifas.ver', 'rifas.validar', 'rifas.cancelar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $rol = Role::findOrCreate('rifa_qa_'.$project->id, 'web');
        $rol->syncPermissions(['rifas.ver', 'rifas.validar', 'rifas.cancelar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $u->id, 'role' => 'editor']);
        Employee::create(['project_id' => $project->id, 'user_id' => $u->id,
            'name' => 'QA', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'active_project_id' => $project->id, 'comercial_project_id' => $project->id,
        ]);
    }
}
