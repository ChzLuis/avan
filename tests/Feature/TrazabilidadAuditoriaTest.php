<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\OrderEvent;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Lo que una auditoria necesita poder demostrar.
 *
 * No basta con que la aplicacion funcione: cuando un gerente pregunta "quien
 * bajo este precio" o "quien borro esta cotizacion", el sistema tiene que
 * poder responder con una fecha, un nombre y el valor anterior. Antes de esto
 * el historial contaba QUE paso, pero no quien lo hizo ni desde que valor, y
 * editar o eliminar no dejaban rastro ninguno.
 */
class TrazabilidadAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
                  'view-quotes', 'manage-quotes'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Auditoria QA', 'slug' => 'auditoria-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        $rol = Role::findOrCreate('auditoria_qa', 'web');
        $rol->syncPermissions(['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar']);
        $this->usuario = User::factory()->create(['is_superadmin' => 0, 'name' => 'Rosa Vendedora']);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->usuario->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $this->usuario->id,
            'name' => 'Rosa', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $this->usuario->syncRoles([$rol->name]);
        $this->actingAs($this->usuario)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    private function crearPorApi(): Quote
    {
        $this->postJson('/bixosales/cotizaciones', [
            'client_name' => 'Ferretería Molina',
            'items' => [['description' => 'Taladro', 'price' => '769.00', 'quantity' => 1, 'discount' => 0]],
        ])->assertSuccessful();

        return Quote::latest('id')->first();
    }

    /** Quien creo el documento queda en el documento, no solo en el log. */
    public function test_la_cotizacion_guarda_quien_la_creo(): void
    {
        $quote = $this->crearPorApi();

        $this->assertSame($this->usuario->id, $quote->created_by);
        $this->assertSame('Rosa Vendedora', $quote->autor->name);
    }

    /**
     * El caso que motiva todo esto: alguien baja un precio. Tiene que
     * quedar el importe anterior, el nuevo y el nombre de quien lo hizo.
     */
    public function test_bajar_un_precio_deja_el_valor_anterior_y_el_autor(): void
    {
        $quote = $this->crearPorApi();

        $this->putJson("/bixosales/cotizaciones/{$quote->id}/full", [
            'client_name' => 'Ferretería Molina',
            'status'      => 'draft',
            'items'       => [['description' => 'Taladro', 'price' => '76.00', 'quantity' => 1, 'discount' => 0]],
        ])->assertSuccessful();

        $evento = OrderEvent::where('quote_id', $quote->id)->where('action', 'edited')->latest('id')->first();

        $this->assertNotNull($evento, 'editar el importe no dejo rastro');
        $this->assertSame($this->usuario->id, $evento->user_id);
        // El antes y el despues, no un "documento editado" a secas.
        $this->assertSame('769.00', $evento->meta['detalle']['total']['de']);
        $this->assertSame('76.00', $evento->meta['detalle']['total']['a']);
        $this->assertStringContainsString('769.00', $evento->label);
        $this->assertStringContainsString('76.00', $evento->label);
    }

    /** Cambiar de cliente tambien se registra: es a quien se le factura. */
    public function test_cambiar_el_cliente_queda_registrado(): void
    {
        $quote = $this->crearPorApi();

        $this->putJson("/bixosales/cotizaciones/{$quote->id}/full", [
            'client_name' => 'Otro Cliente SAC',
            'status'      => 'draft',
            'items'       => [['description' => 'Taladro', 'price' => '769.00', 'quantity' => 1, 'discount' => 0]],
        ])->assertSuccessful();

        $evento = OrderEvent::where('quote_id', $quote->id)->where('action', 'edited')->latest('id')->first();

        $this->assertSame('Ferretería Molina', $evento->meta['detalle']['cliente']['de']);
        $this->assertSame('Otro Cliente SAC', $evento->meta['detalle']['cliente']['a']);
    }

    /** Guardar sin cambiar nada no ensucia el historial. */
    public function test_guardar_sin_cambios_no_anota_nada(): void
    {
        $quote = $this->crearPorApi();

        $this->putJson("/bixosales/cotizaciones/{$quote->id}/full", [
            'client_name' => 'Ferretería Molina',
            'status'      => 'draft',
            'items'       => [['description' => 'Taladro', 'price' => '769.00', 'quantity' => 1, 'discount' => 0]],
        ])->assertSuccessful();

        $this->assertSame(0, OrderEvent::where('quote_id', $quote->id)->where('action', 'edited')->count());
    }

    /**
     * Eliminar era el agujero mas grande: el documento desaparecia con su
     * numero, su cliente y su importe, y no quedaba constancia de nada.
     */
    public function test_eliminar_deja_constancia_de_lo_que_habia(): void
    {
        $quote = $this->crearPorApi();
        $numero = $quote->etiqueta;

        $this->deleteJson("/bixosales/cotizaciones/{$quote->id}")->assertSuccessful();

        $evento = OrderEvent::where('action', 'deleted')->latest('id')->first();

        $this->assertNotNull($evento, 'eliminar no dejo rastro');
        $this->assertSame($this->usuario->id, $evento->user_id);
        $this->assertSame($numero, $evento->meta['numero']);
        $this->assertSame('Ferretería Molina', $evento->meta['cliente']);
        $this->assertSame('769.00', $evento->meta['total']);
        // Y el documento ya no existe: la prueba es que el rastro sobrevive.
        $this->assertNull(Quote::find($quote->id));
    }

    /**
     * Un registro de auditoria que se puede reescribir no prueba nada: quien
     * quisiera tapar un cambio solo tendria que editar su propio rastro.
     */
    public function test_el_historial_no_se_puede_alterar(): void
    {
        $quote = $this->crearPorApi();
        $evento = OrderEvent::where('quote_id', $quote->id)->firstOrFail();

        try {
            $evento->update(['action' => 'otra_cosa']);
            $this->fail('el historial admitio una modificacion');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('auditoría', $e->getMessage());
        }

        try {
            $evento->delete();
            $this->fail('el historial admitio un borrado');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('auditoría', $e->getMessage());
        }

        $this->assertSame('created', $evento->fresh()->action);
    }

    /** La actividad que se lee en pantalla dice quien hizo cada cosa. */
    public function test_la_actividad_muestra_el_autor_de_cada_hecho(): void
    {
        $quote = $this->crearPorApi();

        $eventos = $this->getJson("/bixosales/cotizaciones/{$quote->id}/events")
            ->assertSuccessful()->json('eventos');

        $this->assertNotEmpty($eventos);
        $this->assertSame('Rosa Vendedora', $eventos[0]['quien']);
    }

    /**
     * Lo que hace el CLIENTE desde su enlace no tiene usuario del panel: se
     * dice que fue el cliente, no se deja en blanco ni se atribuye a nadie.
     */
    public function test_lo_que_hace_el_cliente_se_atribuye_al_cliente(): void
    {
        $quote = $this->crearPorApi();
        OrderEvent::log($this->project->id, 'accepted_by_client', [], null, $quote->id);
        // El evento del cliente se guarda sin usuario de panel.
        OrderEvent::where('quote_id', $quote->id)->where('action', 'accepted_by_client')
            ->update(['user_id' => null]);

        $eventos = collect($this->getJson("/bixosales/cotizaciones/{$quote->id}/events")->json('eventos'));
        $delCliente = $eventos->firstWhere('titulo', 'Cotización aceptada por el cliente');

        $this->assertSame('el cliente', $delCliente['quien']);
    }
}
