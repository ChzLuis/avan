<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Modules\Finanzas\Jobs\DarDeBajaGuiaEnSunat;
use App\Modules\Finanzas\Models\GuiaRemision;
use App\Modules\Finanzas\Support\Sunat\GuiaRemisionBaja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Baja de una guía de remisión ante SUNAT.
 *
 * Una guía aceptada NO se borra: se comunica su baja. Hasta ahora el sistema
 * lo impedía con un aviso ("se anula ante SUNAT") pero no ofrecía cómo, y el
 * usuario tenía que entrar al portal de SUNAT con su clave SOL.
 *
 * Lo que se vigila aquí: que no se anule lo que no se puede, que el plazo de
 * SUNAT se respete ANTES de mandar nada, que la guía no quede marcada de baja
 * si la comunicación no entró, y que el correlativo no choque con el de las
 * facturas del mismo día.
 */
class GuiaBajaSunatTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        foreach (['invoices.ver', 'invoices.crear', 'invoices.anular'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Distribuidora Andina', 'slug' => 'dist-andina',
            'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'Facturación', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('despacho_qa', 'web')
            ->syncPermissions(['invoices.ver', 'invoices.crear', 'invoices.anular']);
        $this->user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->user->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $this->user->id,
            'name' => 'Despachador', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $this->user->syncRoles([$rol->name]);

        $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    private function guia(array $extra = []): GuiaRemision
    {
        return GuiaRemision::create(array_merge([
            'project_id'          => $this->project->id,
            'serie'               => 'T001',
            'correlativo'         => 1,
            'numero'              => 'T001-00000001',
            'emisor_ruc'          => '20123456789',
            'emisor_razon_social' => 'Distribuidora Andina SAC',
            'destinatario_nombre' => 'Bodega El Sol',
            'motivo_codigo'       => '01',
            'fecha_traslado'      => now()->toDateString(),
            'modalidad'           => '02',
            'peso_total'          => 120.5,
            'peso_unidad'         => 'KGM',
            'partida_direccion'   => 'Av. Lima 100',
            'llegada_direccion'   => 'Jr. Cusco 200',
            'sunat_status'        => 'accepted',
            'sunat_sent_at'       => now(),
        ], $extra));
    }

    // ═══ QUÉ SE PUEDE ANULAR ═════════════════════════════════════════════

    public function test_una_guia_aceptada_se_puede_dar_de_baja(): void
    {
        $g = $this->guia();

        $r = $this->postJson(route('guias.baja', $g), ['motivo' => 'Error en la placa']);

        $r->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('pending', $g->fresh()->baja_estado);
        $this->assertSame('Error en la placa', $g->fresh()->baja_motivo);
        Queue::assertPushed(DarDeBajaGuiaEnSunat::class);
    }

    public function test_una_guia_sin_enviar_no_se_da_de_baja_se_borra(): void
    {
        $g = $this->guia(['sunat_status' => null, 'sunat_sent_at' => null]);

        $r = $this->postJson(route('guias.baja', $g), ['motivo' => 'Me equivoqué']);

        $r->assertStatus(422);
        $this->assertNull($g->fresh()->baja_estado);
        Queue::assertNotPushed(DarDeBajaGuiaEnSunat::class);
    }

    public function test_una_guia_ya_dada_de_baja_no_se_anula_dos_veces(): void
    {
        $g = $this->guia(['baja_estado' => 'accepted', 'baja_at' => now()]);

        $this->postJson(route('guias.baja', $g), ['motivo' => 'Otra vez'])->assertStatus(422);

        Queue::assertNotPushed(DarDeBajaGuiaEnSunat::class);
    }

    public function test_no_se_pide_la_baja_dos_veces_mientras_esta_en_curso(): void
    {
        $g = $this->guia(['baja_estado' => 'pending']);

        $this->postJson(route('guias.baja', $g), ['motivo' => 'Doble clic'])->assertStatus(422);

        Queue::assertNotPushed(DarDeBajaGuiaEnSunat::class);
    }

    public function test_una_baja_rechazada_se_puede_reintentar(): void
    {
        $g = $this->guia(['baja_estado' => 'rejected', 'baja_error' => 'Se cayó la conexión']);

        $this->postJson(route('guias.baja', $g), ['motivo' => 'Error en la placa'])->assertOk();

        // El error viejo no se queda pegado confundiendo al usuario.
        $this->assertNull($g->fresh()->baja_error);
        $this->assertSame('pending', $g->fresh()->baja_estado);
    }

    // ═══ EL PLAZO LO PONE SUNAT ══════════════════════════════════════════

    public function test_pasados_siete_dias_sunat_ya_no_admite_la_baja(): void
    {
        $g = $this->guia(['sunat_sent_at' => now()->subDays(8)]);

        $r = $this->postJson(route('guias.baja', $g), ['motivo' => 'Tarde']);

        // Se avisa AQUI: si no, el rechazo llegaria en segundo plano, con el
        // camion ya despachado y sin que nadie mire.
        $r->assertStatus(422);
        Queue::assertNotPushed(DarDeBajaGuiaEnSunat::class);
    }

    public function test_el_septimo_dia_todavia_entra(): void
    {
        $g = $this->guia(['sunat_sent_at' => now()->subDays(7)]);

        $this->postJson(route('guias.baja', $g), ['motivo' => 'Justo a tiempo'])->assertOk();
    }

    // ═══ EL MOTIVO ═══════════════════════════════════════════════════════

    public function test_el_motivo_es_obligatorio(): void
    {
        $g = $this->guia();

        $this->postJson(route('guias.baja', $g), [])->assertStatus(422);
        $this->postJson(route('guias.baja', $g), ['motivo' => 'x'])->assertStatus(422);

        Queue::assertNotPushed(DarDeBajaGuiaEnSunat::class);
    }

    // ═══ AISLAMIENTO Y PERMISOS ══════════════════════════════════════════

    public function test_no_puedo_anular_la_guia_de_otro_negocio(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajena', 'slug' => 'ajena', 'is_active' => true,
        ]);
        $g = $this->guia(['project_id' => $otro->id]);

        // 404, no 403: el scope de tenant la oculta antes de llegar al
        // controlador, asi que ni siquiera se confirma que exista.
        $this->postJson(route('guias.baja', $g), ['motivo' => 'Intruso'])->assertNotFound();

        $this->assertNull(GuiaRemision::allProjects()->find($g->id)->baja_estado);
    }

    public function test_sin_permiso_de_anular_no_se_da_de_baja(): void
    {
        $rol = Role::findOrCreate('solo_mira', 'web')->syncPermissions(['invoices.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Mirón', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);

        $g = $this->guia();

        $this->actingAs($u)->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('guias.baja', $g), ['motivo' => 'No debería poder'])
            ->assertForbidden();
    }

    // ═══ LO QUE SE LE MANDA A SUNAT ══════════════════════════════════════

    public function test_el_payload_declara_la_guia_como_tipo_09(): void
    {
        $g = $this->guia(['baja_motivo' => 'Error en la placa']);

        $p = (new GuiaRemisionBaja())->payload($g);

        // Catalogo 01: 09 = guia de remision remitente. Con otro codigo SUNAT
        // daria de baja un documento que no existe.
        $this->assertSame('09', $p['details'][0]['tipoDoc']);
        $this->assertSame('T001', $p['details'][0]['serie']);
        $this->assertSame('Error en la placa', $p['details'][0]['desMotivoBaja']);
    }

    public function test_el_correlativo_va_sin_ceros_a_la_izquierda(): void
    {
        $g = $this->guia(['numero' => 'T001-00000015']);

        $p = (new GuiaRemisionBaja())->payload($g);

        // "00000015" lo rechaza SUNAT en la comunicacion de baja.
        $this->assertSame('15', $p['details'][0]['correlativo']);
    }

    public function test_el_correlativo_de_la_baja_cuenta_facturas_y_guias_del_dia(): void
    {
        // Dos comunicaciones del mismo dia con el mismo numero: SUNAT rechaza
        // la segunda. Si cada documento llevara su propia cuenta, una factura
        // y una guia anuladas hoy saldrian las dos con el correlativo 1.
        $yaDeBaja = $this->guia([
            'numero' => 'T001-00000009', 'correlativo' => 9,
            'baja_estado' => 'accepted', 'baja_at' => now(),
        ]);

        $nueva = $this->guia(['numero' => 'T001-00000010', 'correlativo' => 10]);

        $p = (new GuiaRemisionBaja())->payload($nueva);

        $this->assertSame('2', $p['correlativo'],
            'La segunda baja del día debe llevar el correlativo 2, no repetir el 1.');
        $this->assertNotNull($yaDeBaja->baja_at);
    }

    // ═══ LA GUÍA NO QUEDA DE BAJA SI SUNAT NO LO CONFIRMÓ ════════════════

    public function test_sin_proveedor_configurado_la_baja_se_marca_rechazada(): void
    {
        $g = $this->guia(['baja_estado' => 'pending']);

        $r = (new GuiaRemisionBaja())->anular($g);

        $this->assertFalse($r['ok']);
        $this->assertSame('rejected', $g->fresh()->baja_estado);
        // Lo importante: NO queda como dada de baja aqui mientras sigue viva
        // en SUNAT, que es el error que ya se cometio con las facturas.
        $this->assertNotSame('accepted', $g->fresh()->baja_estado);
        $this->assertStringContainsString('facturación', $g->fresh()->baja_error);
    }

    public function test_el_fallo_del_job_deja_la_guia_viva_y_con_el_motivo(): void
    {
        $g = $this->guia(['baja_estado' => 'pending', 'status' => 'cancelled']);

        (new DarDeBajaGuiaEnSunat($g->id))->failed(new \RuntimeException('se cayó la red'));

        $g->refresh();
        $this->assertSame('rejected', $g->baja_estado);
        // Si la baja no entro, la guia SIGUE surtiendo efecto: no puede
        // quedarse figurando como anulada.
        $this->assertSame('issued', $g->status);
        $this->assertStringContainsString('se cayó la red', $g->baja_error);
    }

    // ═══ LO QUE VE EL USUARIO ════════════════════════════════════════════

    /**
     * El boton lleva el numero en su `title`, y el dialogo (que existe
     * siempre, oculto) solo dice "Dar de baja": se busca el title para no
     * confundir uno con otro.
     */
    public function test_el_historico_ofrece_dar_de_baja_una_guia_aceptada(): void
    {
        $g = $this->guia();

        $this->get(route('guias.consulta'))->assertOk()
            ->assertSee('Dar de baja '.$g->numero.' ante SUNAT', false);
    }

    public function test_el_historico_no_ofrece_anular_una_guia_fuera_de_plazo(): void
    {
        $g = $this->guia(['sunat_sent_at' => now()->subDays(20)]);

        $this->get(route('guias.consulta'))->assertOk()
            ->assertDontSee('Dar de baja '.$g->numero.' ante SUNAT', false);
    }

    public function test_el_historico_marca_la_guia_ya_dada_de_baja(): void
    {
        $this->guia(['baja_estado' => 'accepted', 'baja_at' => now()]);

        $this->get(route('guias.consulta'))->assertOk()->assertSee('De baja');
    }
}
