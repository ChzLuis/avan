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
 * Contrato entre el servidor y la interfaz de Cotizaciones.
 *
 * La vista no vuelve a deducir permisos con @can sueltos: recibe `$puede` ya
 * resuelto. Y no puede enseñar vocabulario interno: hasta F1c el stepper
 * pintaba las claves crudas 'draft/sent/accepted/rejected' porque iteraba el
 * objeto al reves (Alpine entrega (valor, clave), no (clave, valor)), lo que
 * ademas hacia que pulsar un paso enviara un estado invalido al servidor.
 */
class QuotesUiContractTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
                  'view-quotes', 'manage-quotes'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'UI QA', 'slug' => 'ui-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function entrar(array $permisos): User
    {
        $rol = Role::findOrCreate('ui_' . md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);

        return $u;
    }

    private function quote(array $attrs = []): Quote
    {
        $q = Quote::create(array_merge([
            'project_id' => $this->project->id, 'client_name' => 'Cliente UI',
            'status' => 'accepted', 'total' => '100.00', 'token' => str()->random(24),
        ], $attrs));
        $q->items()->create(['description' => 'X', 'price' => '100.00', 'quantity' => 1, 'discount' => 0]);

        return $q;
    }

    public function test_la_vista_recibe_las_capacidades_resueltas(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);

        $puede = $this->get('/bixosales/cotizaciones')->assertSuccessful()->viewData('puede');

        $this->assertIsArray($puede);
        foreach (['ver', 'crear', 'editar', 'eliminar', 'convertir'] as $clave) {
            $this->assertArrayHasKey($clave, $puede);
        }
        $this->assertTrue($puede['convertir']);
        $this->assertFalse($puede['crear']);
    }

    public function test_un_lector_no_recibe_capacidad_de_convertir(): void
    {
        $this->entrar(['quotes.ver']);

        $puede = $this->get('/bixosales/cotizaciones')->viewData('puede');

        $this->assertTrue($puede['ver']);
        $this->assertFalse($puede['convertir'], 'no se enseña un boton que acabaria en 403');
    }

    public function test_sin_modulo_de_pedidos_la_vista_no_ofrece_convertir(): void
    {
        $modulo = Module::where('key', 'orders')->first();
        $this->project->modules()->updateExistingPivot($modulo->id, ['is_active' => false]);
        $this->entrar(['quotes.ver', 'quotes.editar']);

        $puede = $this->get('/bixosales/cotizaciones')->viewData('puede');

        $this->assertTrue($puede['editar']);
        $this->assertFalse($puede['convertir'], 'sin Pedidos, el pedido no tendria donde vivir');
    }

    public function test_el_estado_pinta_etiquetas_y_envia_claves(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // El stepper de cuatro botones se sustituyo por un desplegable: ocupaba
        // 300 px de ancho y una franja entera de alto para decir una sola cosa.
        // Lo que NO cambia es el contrato: al usuario se le enseña la etiqueta
        // en español y al servidor se le manda la clave canonica.
        $this->assertStringContainsString('<option value="draft">Borrador</option>', $html);
        $this->assertStringContainsString('<option value="sent">Enviada</option>', $html);
        $this->assertStringContainsString('<option value="accepted">Aceptada</option>', $html);
        $this->assertStringContainsString('<option value="rejected">Rechazada</option>', $html);
        $this->assertStringContainsString('setStatus($event.target.value)', $html);

        // Y jamas al reves: mandar la etiqueta que el validador rechaza.
        foreach (['setStatus(\'Borrador\')', 'setStatus(\'Enviada\')', 'value="Borrador"'] as $error) {
            $this->assertStringNotContainsString($error, $html);
        }
    }

    /**
     * F1c (hallazgo de revision humana de Codex): con la cotizacion en
     * Borrador, "Rechazada" salia VERDE porque indexOf('rejected') sobre el
     * flujo positivo da -1 y `0 > -1` la marcaba completada. Un paso fuera de
     * la secuencia jamas puede presentarse como exito.
     */
    public function test_rechazada_nunca_se_presenta_como_completada(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote(['status' => 'draft']);

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // El color lo decide una clase por estado, no una comparacion de
        // posiciones dentro de un flujo: rechazada es roja y aceptada verde,
        // y ninguna puede heredar el color de la otra.
        // El color ya no se escribe suelto en cada regla: sale de los
        // tokens del contrato visual. Lo que este test protege no es el hex
        // —cambia con el diseno— sino que rechazada viva en la familia de
        // PELIGRO y aceptada en la de EXITO, y que jamas compartan token.
        $this->assertStringContainsString('.q-estado-rejected { background-color:var(--q-dan-soft); color:var(--q-dan)', $html);
        $this->assertStringContainsString('.q-estado-accepted { background-color:var(--q-ok-soft); color:var(--q-ok)', $html);
        $this->assertStringContainsString('--q-dan:#DC2626', $html);
        $this->assertStringContainsString('--q-ok:#16A34A', $html);
        $this->assertStringContainsString(':class="\'q-estado-\'+form.status"', $html);

        // La formula rota no puede volver.
        $this->assertStringNotContainsString("indexOf('rejected')", $html);
    }

    public function test_el_boton_de_convertir_depende_de_permiso_estado_y_modulo(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // La condicion es una sola, resuelta en el componente.
        $this->assertStringContainsString('puedeConvertirAhora', $html);
        $this->assertStringContainsString("this.puede.convertir", $html);
        $this->assertStringContainsString("this.form.status === 'accepted'", $html);
    }

    public function test_el_modal_de_conversion_es_accesible(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        foreach ([
            'role="dialog"', 'aria-modal="true"', 'aria-labelledby="tituloConvertir"',
            'aria-describedby="descConvertir"', 'x-trap.noscroll', 'keydown.escape',
            ':aria-busy=', 'role="status"', 'aria-live="polite"', 'role="alert"',
        ] as $marca) {
            $this->assertStringContainsString($marca, $html, "falta en el modal: {$marca}");
        }

        // Dice QUE va a pasar, no "¿estas seguro?"
        $this->assertStringContainsString('Se creará un pedido con', $html);
        $this->assertStringNotContainsString('¿Estás seguro', $html);
    }

    public function test_la_relacion_con_el_pedido_se_muestra_en_ambos_lados(): void
    {
        // Hace falta 'orders.ver' para entrar tambien al otro lado del vinculo.
        Permission::findOrCreate('orders.ver', 'web');
        $this->entrar(['quotes.ver', 'quotes.editar', 'orders.ver']);
        $q = $this->quote();
        $this->postJson("/bixosales/cotizaciones/{$q->id}/convertir-pedido")->assertSuccessful();

        $htmlCot = $this->get('/bixosales/cotizaciones')->getContent();
        $this->assertStringContainsString("'PED-' + pedidoDeEstaCotizacion", $htmlCot);

        $htmlPed = $this->get('/bixosales/pedidos')->getContent();
        $this->assertStringContainsString("'COT-' + selected.quote_id", $htmlPed);
        $this->assertStringContainsString('Originada en', $htmlPed);
    }

    public function test_la_vista_no_expone_claves_crudas_como_texto(): void
    {
        $this->entrar(['quotes.ver']);
        $this->quote(['status' => 'converted']);

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // El vocabulario visible es español; las claves viven en atributos.
        foreach (['>draft<', '>sent<', '>accepted<', '>rejected<', '>converted<'] as $crudo) {
            $this->assertStringNotContainsString($crudo, $html, "clave cruda visible: {$crudo}");
        }
    }

    /**
     * La Actividad del panel derecho tiene que salir de hechos registrados.
     * `order_events` llevaba tiempo escribiendo la vida de la cotizacion
     * —creada, enviada, aceptada por el cliente, convertida— pero el unico
     * endpoint de eventos filtraba por `order_id`: se escribia un historial
     * que nadie podia leer. Este contrato fija que ahora se lee, y que se lee
     * SOLO el del propio negocio.
     */
    public function test_la_actividad_devuelve_eventos_reales_de_la_cotizacion(): void
    {
        $this->entrar(['quotes.ver']);
        $q = $this->quote();

        OrderEvent::create([
            'project_id' => $this->project->id, 'quote_id' => $q->id,
            'action' => 'created', 'meta' => [], 'created_at' => now()->subHour(),
        ]);
        OrderEvent::create([
            'project_id' => $this->project->id, 'quote_id' => $q->id,
            'action' => 'quote_sent', 'meta' => [], 'created_at' => now(),
        ]);

        $eventos = $this->getJson("/bixosales/cotizaciones/{$q->id}/events")
            ->assertSuccessful()
            ->json('eventos');

        $this->assertCount(2, $eventos);
        foreach ($eventos as $e) {
            foreach (['titulo', 'detalle', 'hace', 'fecha'] as $clave) {
                $this->assertArrayHasKey($clave, $e);
                $this->assertNotSame('', (string) $e[$clave]);
            }
        }
    }

    public function test_la_actividad_de_otro_negocio_no_se_lee(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajena = Quote::create([
            'project_id' => $otro->id, 'client_name' => 'X',
            'status' => 'draft', 'total' => '10.00', 'token' => str()->random(24),
        ]);

        $this->entrar(['quotes.ver']);

        // Lo que se exige es que NO se lea: 403 o 404 valen, filtrarse no.
        $r = $this->getJson("/bixosales/cotizaciones/{$ajena->id}/events");
        $this->assertContains($r->status(), [403, 404]);
        $this->assertStringNotContainsString('titulo', $r->getContent());
    }
}
