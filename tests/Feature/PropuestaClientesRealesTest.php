<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Ventas\Models\Proposal;
use App\Modules\Ventas\Support\DemosPorRubro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Clientes REALES en la propuesta, y edicion de una propuesta ya creada.
 *
 * Dos huecos vistos el 2026-09-22:
 *
 * 1. La lista de tiendas de ejemplo solo tenia DEMOS. MegaHogar, Tecsist,
 *    Electro Jara y los demas negocios que ya estan en produccion no salian,
 *    asi que se mandaban propuestas con escaparates de muestra pudiendo
 *    ensenar clientes vendiendo de verdad.
 * 2. La ruta `update` existia desde el principio pero no habia por donde
 *    llamarla: corregir un precio obligaba a borrar y rehacer la propuesta,
 *    y el enlace ya compartido con el cliente moria.
 */
class PropuestaClientesRealesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();

        $this->super = User::factory()->create(['is_superadmin' => 1]);
        $this->project = Project::create([
            'owner_id' => $this->super->id,
            'name' => 'Eskala', 'slug' => 'eskala-demo', 'is_active' => true,
        ]);
    }

    private function como()
    {
        return $this->actingAs($this->super)->withSession([
            'active_project_id' => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
    }

    private function propuesta(array $extra = []): Proposal
    {
        return Proposal::create(array_merge([
            'project_id'   => $this->project->id,
            'client_name'  => 'Daniel',
            'rubro'        => 'Tecnología - Celulares',
            'price'        => 590,
            'price_first'  => 295,
            'price_second' => 295,
            'valid_days'   => 15,
            'status'       => 'enviada',
            'token'        => \Illuminate\Support\Str::random(40),
        ], $extra));
    }

    // ═══ CLIENTES REALES ═════════════════════════════════════════════════

    public function test_la_lista_incluye_los_clientes_en_produccion(): void
    {
        $r = $this->como()->get('/bixoadmin/proposals');

        $r->assertOk()
          ->assertSee('MegaHogar')
          ->assertSee('Tecsist Solutions')
          ->assertSee('Electro Jara')
          ->assertSee('Baby Toncito')
          ->assertSee('Distribuidora Muruhuay')
          ->assertSee('Market Huacho Express');
    }

    public function test_los_clientes_reales_van_antes_que_las_demos(): void
    {
        $ops = DemosPorRubro::opciones();

        $primerDemo = null;
        foreach ($ops as $i => $o) {
            if (! ($o['real'] ?? false)) { $primerDemo = $i; break; }
        }

        // Todo lo anterior al primer escaparate de muestra es cliente real:
        // son el mejor argumento y se eligen primero.
        foreach (array_slice($ops, 0, $primerDemo) as $o) {
            $this->assertTrue($o['real']);
        }
        $this->assertGreaterThan(0, $primerDemo, 'Ningún cliente real encabeza la lista.');
    }

    public function test_se_distinguen_de_las_demos(): void
    {
        $this->como()->get('/bixoadmin/proposals')->assertOk()->assertSee('CLIENTE REAL');
    }

    public function test_un_rubro_con_cliente_real_lo_ofrece_antes_que_su_demo(): void
    {
        // Ferretería tiene las dos cosas: Electro Jara (real) y GABDE (demo).
        $lista = DemosPorRubro::delRubro('ferretería');

        $this->assertSame('Electro Jara', $lista[0]['nombre']);
    }

    public function test_un_rubro_que_solo_tiene_cliente_real_no_se_queda_vacio(): void
    {
        // Muebles y celulares no tienen demo propia: antes devolvían nada.
        $this->assertNotEmpty(DemosPorRubro::delRubro('muebles para el hogar'));
        $this->assertNotEmpty(DemosPorRubro::delRubro('venta de celulares'));
    }

    public function test_los_rubros_nuevos_no_rompen_los_textos(): void
    {
        // `hogar` no está en APERTURAS: sin acceso seguro esto era un error
        // fatal y la pantalla entera se caía.
        $this->assertIsString(DemosPorRubro::apertura('muebles'));
        $this->assertIsString(DemosPorRubro::motivo('celulares'));
        $this->assertNull(DemosPorRubro::apertura('algo que no existe'));
    }

    public function test_ninguna_url_de_cliente_real_apunta_a_una_demo(): void
    {
        foreach (DemosPorRubro::clientesReales() as $c) {
            $this->assertStringNotContainsString('arindg.com/demo', $c['url'],
                $c['nombre'].' apunta a una demo, no a su tienda real.');
            $this->assertStringStartsWith('https://', $c['url']);
        }
    }

    // ═══ EDITAR UNA PROPUESTA YA CREADA ══════════════════════════════════

    public function test_la_pantalla_ofrece_editar_cada_propuesta(): void
    {
        $this->propuesta();

        $this->como()->get('/bixoadmin/proposals')->assertOk()->assertSee('Editar');
    }

    public function test_editar_corrige_el_precio_sin_cambiar_el_enlace(): void
    {
        $p = $this->propuesta();
        $tokenAntes = $p->token;

        $r = $this->como()->putJson('/bixoadmin/proposals/'.$p->id, [
            'client_name' => 'Daniel',
            'rubro'       => 'Tecnología - Celulares',
            'price'       => 690,
            'status'      => 'enviada',
        ]);

        $r->assertOk();
        $p->refresh();
        $this->assertEquals(690, $p->price);
        // El enlace ya se mandó por WhatsApp: si cambiara, el cliente abriría
        // una página muerta.
        $this->assertSame($tokenAntes, $p->token);
    }

    public function test_no_puedo_editar_la_propuesta_de_otro_negocio(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajena', 'slug' => 'ajena', 'is_active' => true,
        ]);
        $p = $this->propuesta(['project_id' => $otro->id]);

        $this->como()->putJson('/bixoadmin/proposals/'.$p->id, [
            'client_name' => 'Intruso', 'price' => 1,
        ])->assertForbidden();

        $this->assertSame('Daniel', $p->fresh()->client_name);
    }

    public function test_editar_una_propuesta_sin_token_no_revienta(): void
    {
        // Antes: UrlGenerationException y un 500 en la cara del usuario.
        $p = $this->propuesta(['token' => null]);

        $r = $this->como()->putJson('/bixoadmin/proposals/'.$p->id, [
            'client_name' => 'Daniel', 'price' => 590, 'status' => 'enviada',
        ]);

        $r->assertOk();
        $this->assertNotEmpty($p->fresh()->token, 'Se le debe dar un enlace al vuelo.');
    }

    public function test_editar_no_borra_el_numero_de_la_propuesta(): void
    {
        $p = $this->propuesta();
        $numeroAntes = $p->number;

        $this->como()->putJson('/bixoadmin/proposals/'.$p->id, [
            'client_name' => 'Daniel Ramos', 'price' => 590, 'status' => 'enviada',
        ])->assertOk();

        $this->assertSame($numeroAntes, $p->fresh()->number);
        $this->assertSame('Daniel Ramos', $p->fresh()->client_name);
    }
}
