<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El comercio puede atender su Libro de Reclamaciones (2026-09-07).
 *
 * El reclamo se guardaba y se avisaba por correo, pero no habia pantalla: si
 * el correo se perdia, el registro quedaba en la base sin que nadie lo
 * atendiera. INDECOPI obliga a conservarlos y responder en 15 dias habiles.
 */
class LibroReclamacionesPanelTest extends TestCase
{
    use RefreshDatabase;

    private function negocio(): Project
    {
        return Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Mi Tienda', 'slug' => 'lr-'.uniqid(), 'is_active' => true,
        ]);
    }

    private function reclamo(Project $p, array $extra = []): Complaint
    {
        return Complaint::create($extra + [
            'project_id' => $p->id, 'code' => 'REC-'.uniqid(),
            'consumer_name' => 'Juana Pérez', 'document_type' => 'DNI', 'document_number' => '45678912',
            'email' => 'juana@example.com', 'product_or_service' => 'Reflector solar 100W',
            'type' => 'reclamo', 'detail' => 'Llegó con el panel roto.', 'request' => 'Cambio del producto.',
            'terms_accepted' => true, 'status' => 'received',
        ]);
    }

    public function test_el_comercio_ve_sus_reclamos(): void
    {
        $p = $this->negocio();
        $this->reclamo($p);

        $r = $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->get(route('complaints.index'))->assertOk();

        $r->assertSee('Juana Pérez');
        $r->assertSee('Reflector solar 100W');
        $r->assertSee('45678912');
    }

    /** Un negocio no puede ver ni tocar los reclamos de otro. */
    public function test_no_se_cruzan_los_negocios(): void
    {
        $mio = $this->negocio();
        $ajeno = $this->negocio();
        $suyo = $this->reclamo($ajeno, ['consumer_name' => 'Cliente Ajeno']);

        $this->actingAs($mio->owner)->withSession(['active_project_id' => $mio->id])
            ->get(route('complaints.index'))->assertOk()->assertDontSee('Cliente Ajeno');

        $this->actingAs($mio->owner)->withSession(['active_project_id' => $mio->id])
            ->patch(route('complaints.status', $suyo), ['status' => 'closed'])->assertForbidden();

        $this->assertSame('received', $suyo->fresh()->status, 'el reclamo ajeno no debe cambiar');
    }

    public function test_se_puede_cambiar_el_estado(): void
    {
        $p = $this->negocio();
        $c = $this->reclamo($p);

        $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->patch(route('complaints.status', $c), ['status' => 'resolved'])
            ->assertRedirect();

        $this->assertSame('resolved', $c->fresh()->status);
    }

    public function test_un_estado_inventado_se_rechaza(): void
    {
        $p = $this->negocio();
        $c = $this->reclamo($p);

        $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->patch(route('complaints.status', $c), ['status' => 'archivado'])
            ->assertSessionHasErrors('status');

        $this->assertSame('received', $c->fresh()->status);
    }

    public function test_los_filtros_acotan_la_lista(): void
    {
        $p = $this->negocio();
        $this->reclamo($p, ['consumer_name' => 'Ana Reclamo', 'type' => 'reclamo', 'status' => 'received']);
        $this->reclamo($p, ['consumer_name' => 'Beto Queja', 'type' => 'queja', 'status' => 'closed']);

        $ver = fn (array $q) => $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->get(route('complaints.index').'?'.http_build_query($q))->assertOk();

        $ver(['tipo' => 'queja'])->assertSee('Beto Queja')->assertDontSee('Ana Reclamo');
        $ver(['estado' => 'received'])->assertSee('Ana Reclamo')->assertDontSee('Beto Queja');
        $ver(['q' => 'Beto'])->assertSee('Beto Queja')->assertDontSee('Ana Reclamo');
    }

    /** El plazo de INDECOPI se cuenta en dias habiles, saltando el fin de semana. */
    public function test_el_plazo_salta_sabados_y_domingos(): void
    {
        $p = $this->negocio();
        // Un viernes: 15 dias habiles despues cae tres semanas mas tarde.
        $c = $this->reclamo($p);
        $c->created_at = \Illuminate\Support\Carbon::parse('2026-09-04'); // viernes
        $c->save();

        $vence = \App\Http\Controllers\ComplaintController::vence($c->fresh());

        $this->assertFalse($vence->isWeekend(), 'el vencimiento no puede caer en fin de semana');
        $this->assertSame('2026-09-25', $vence->format('Y-m-d'));
    }

    /** Sin ningun reclamo la pantalla explica que pasara, no queda muda. */
    public function test_sin_reclamos_explica_que_esperar(): void
    {
        $p = $this->negocio();

        $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->get(route('complaints.index'))->assertOk()
            ->assertSee('Todavía no hay reclamos ni quejas');
    }
}
