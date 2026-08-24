<?php

namespace Tests\Feature;

use App\Jobs\EnviarGuiaASunat;
use App\Models\Employee;
use App\Models\GuiaRemision;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\Sunat\GuiaRemisionSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La guía de remisión: el documento que viaja con la mercadería.
 *
 * La factura dice qué se vendió; la guía dice cómo viajó. En un control de
 * carretera piden esta, y mover bienes sin ella expone a que los retengan por
 * muy bien facturados que estén.
 */
class GuiaRemisionTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        foreach (['invoices.ver', 'invoices.crear', 'invoices.anular'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Distribuidora Andina', 'slug' => 'dist-andina', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'Facturación', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('despacho_qa', 'web')
            ->syncPermissions(['invoices.ver', 'invoices.crear', 'invoices.anular']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Despachador', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);

        $this->actingAs($u)->withSession(['active_project_id' => $this->project->id]);
    }

    /** Un despacho normal: vehículo propio, conductor conocido. */
    private function datosPrivado(array $extra = []): array
    {
        return array_merge([
            'destinatario_nombre'     => 'Ferretería El Sol EIRL',
            'destinatario_doc_tipo'   => '6',
            'destinatario_doc_numero' => '20600819110',
            'motivo_codigo'           => '01',
            'fecha_traslado'          => now()->addDay()->toDateString(),
            'modalidad'               => GuiaRemision::PRIVADO,
            'peso_total'              => 250.5,
            'peso_unidad'             => 'KGM',
            'bultos'                  => 12,
            'partida_direccion'       => 'Av. Argentina 1234, Lima',
            'llegada_direccion'       => 'Jr. Comercio 456, Huancayo',
            'vehiculo_placa'          => 'ABC-123',
            'conductor_doc_numero'    => '45678912',
            'conductor_nombres'       => 'Luis',
            'conductor_apellidos'     => 'Quispe Mamani',
            'conductor_licencia'      => 'Q45678912',
            'items' => [
                ['description' => 'Cemento Sol', 'unit' => 'BOLSA', 'quantity' => 50],
            ],
        ], $extra);
    }

    public function test_se_emite_una_guia_con_su_numero_y_sus_lineas(): void
    {
        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();

        $guia = GuiaRemision::latest('id')->first();

        $this->assertNotNull($guia);
        $this->assertSame('T001-00000001', $guia->numero, 'la serie de un remitente empieza por T');
        $this->assertSame('01', $guia->motivo_codigo);
        $this->assertSame('Venta', $guia->motivo_descripcion);
        $this->assertCount(1, $guia->items);
        Queue::assertPushed(EnviarGuiaASunat::class);
    }

    /** La unidad se guarda ya traducida al código oficial. */
    public function test_la_unidad_se_guarda_con_el_codigo_de_sunat(): void
    {
        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();

        $this->assertSame('BG', GuiaRemision::latest('id')->first()->items->first()->unit, '"BOLSA" es BG');
    }

    /**
     * En transporte privado responde el negocio con su vehículo; sin placa,
     * conductor y licencia la guía no vale y SUNAT la rechaza.
     */
    public function test_el_transporte_privado_exige_vehiculo_y_conductor(): void
    {
        $sinPlaca = $this->datosPrivado();
        unset($sinPlaca['vehiculo_placa'], $sinPlaca['conductor_licencia']);

        $this->postJson('/guias', $sinPlaca)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vehiculo_placa', 'conductor_licencia']);
    }

    /** Y en transporte público responde el transportista: hace falta su RUC. */
    public function test_el_transporte_publico_exige_transportista(): void
    {
        $publico = $this->datosPrivado(['modalidad' => GuiaRemision::PUBLICO]);
        unset($publico['vehiculo_placa'], $publico['conductor_doc_numero'],
              $publico['conductor_nombres'], $publico['conductor_apellidos'], $publico['conductor_licencia']);

        $this->postJson('/guias', $publico)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transportista_ruc', 'transportista_razon_social']);
    }

    /** El motivo de traslado sale del catálogo 20, no es texto libre. */
    public function test_el_motivo_de_traslado_sale_del_catalogo(): void
    {
        $this->postJson('/guias', $this->datosPrivado(['motivo_codigo' => '99']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['motivo_codigo']);
    }

    /** Sin peso no hay guía: es lo primero que miran en un control. */
    public function test_la_guia_exige_peso(): void
    {
        $sinPeso = $this->datosPrivado();
        unset($sinPeso['peso_total']);

        $this->postJson('/guias', $sinPeso)->assertStatus(422)->assertJsonValidationErrors(['peso_total']);
    }

    /** Ni sin decir de dónde sale y a dónde llega. */
    public function test_la_guia_exige_los_dos_puntos(): void
    {
        $sinDirecciones = $this->datosPrivado();
        unset($sinDirecciones['partida_direccion'], $sinDirecciones['llegada_direccion']);

        $this->postJson('/guias', $sinDirecciones)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['partida_direccion', 'llegada_direccion']);
    }

    /** Dos despachos seguidos no comparten número. */
    public function test_el_correlativo_de_guias_no_se_repite(): void
    {
        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();
        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();

        $numeros = GuiaRemision::pluck('numero')->all();

        $this->assertSame(['T001-00000001', 'T001-00000002'], $numeros);
    }

    /** Una guía aceptada por SUNAT no se borra. */
    public function test_una_guia_aceptada_no_se_borra(): void
    {
        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();
        $guia = GuiaRemision::latest('id')->first();

        $guia->update(['sunat_status' => 'accepted']);
        $this->deleteJson('/guias/'.$guia->id)->assertStatus(422);
        $this->assertDatabaseHas('guias_remision', ['id' => $guia->id]);

        // Sin aceptar todavía sí se puede retirar.
        $guia->update(['sunat_status' => 'error']);
        $this->deleteJson('/guias/'.$guia->id)->assertSuccessful();
        $this->assertDatabaseMissing('guias_remision', ['id' => $guia->id]);
    }

    /**
     * El cuerpo que sale hacia el proveedor.
     *
     * En transporte privado van vehículo y chofer; en público, el
     * transportista. Mandar los dos —o ninguno— es motivo de rechazo.
     */
    public function test_el_payload_declara_el_transporte_que_corresponde(): void
    {
        $this->project->settings()->create(['key' => 'billing_provider', 'value' => 'apisperu']);
        $this->project->settings()->create(['key' => 'apisperu_token', 'value' => 'token-de-prueba']);

        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();
        $guia = GuiaRemision::latest('id')->first()->load('items', 'project', 'invoice');

        $payload = (new GuiaRemisionSender())->payloadApisPeru($guia);

        $this->assertSame('09', $payload['tipoDoc'], 'guía de remisión remitente');
        $this->assertSame('T001', $payload['serie']);
        $this->assertSame('01', $payload['envio']['codTraslado']);
        $this->assertSame(GuiaRemision::PRIVADO, $payload['envio']['modTraslado']);
        $this->assertSame(250.5, $payload['envio']['pesoTotal']);
        $this->assertSame('ABC-123', $payload['envio']['vehiculo']['placa']);
        $this->assertArrayNotHasKey('transportista', $payload['envio'], 'en privado no hay transportista');
        $this->assertSame('BG', $payload['details'][0]['unidad']);
    }

    /** Nubefact recibe los mismos datos con otros nombres: tambien se comprueba. */
    public function test_el_payload_de_nubefact_lleva_el_traslado_completo(): void
    {
        $this->postJson('/guias', $this->datosPrivado())->assertSuccessful();
        $guia = GuiaRemision::latest('id')->first()->load('items', 'project', 'invoice');

        $payload = (new GuiaRemisionSender())->payloadNubefact($guia);

        $this->assertSame('generar_guia', $payload['operacion']);
        $this->assertSame(7, $payload['tipo_de_comprobante'], 'guía de remisión remitente');
        $this->assertSame('01', $payload['motivo_de_traslado']);
        $this->assertSame(250.5, $payload['peso_bruto_total']);
        $this->assertSame('KGM', $payload['peso_bruto_unidad_de_medida']);
        $this->assertSame('ABC-123', $payload['transportista_placa_numero']);
        $this->assertSame('Q45678912', $payload['conductor_numero_licencia']);
        $this->assertSame('BG', $payload['items'][0]['unidad_de_medida']);
    }

    public function test_el_payload_publico_declara_al_transportista(): void
    {
        $publico = $this->datosPrivado([
            'modalidad'                  => GuiaRemision::PUBLICO,
            'transportista_ruc'          => '20512345678',
            'transportista_razon_social' => 'Transportes Rápidos SAC',
        ]);

        $this->postJson('/guias', $publico)->assertSuccessful();
        $guia = GuiaRemision::latest('id')->first()->load('items', 'project', 'invoice');

        $payload = (new GuiaRemisionSender())->payloadApisPeru($guia);

        $this->assertSame('20512345678', $payload['envio']['transportista']['numDoc']);
        $this->assertArrayNotHasKey('vehiculo', $payload['envio'], 'en público el vehículo lo pone el transportista');
        $this->assertArrayNotHasKey('chofer', $payload['envio']);
    }

}
