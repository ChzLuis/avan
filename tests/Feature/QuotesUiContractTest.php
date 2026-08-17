<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Quote;
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

    public function test_el_stepper_pinta_etiquetas_y_envia_claves(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // Alpine entrega (valor, clave): el nombre de las variables importa.
        $this->assertStringContainsString('x-for="(etiqueta, clave) in {draft:', $html);
        $this->assertStringContainsString('x-text="etiqueta"', $html, 'se pinta la etiqueta en español');
        $this->assertStringContainsString('@click="setStatus(clave)"', $html, 'se envia la clave canonica');

        // El error exacto que habia: pintar la clave y enviar la etiqueta.
        $this->assertStringNotContainsString('x-for="(s,label) in', $html);
        $this->assertStringNotContainsString('@click="setStatus(s)"', $html);
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

        // La clase se decide en UN helper con semantica explicita…
        $this->assertStringContainsString('claseEstado(clave)', $html);
        $this->assertStringContainsString(":class=\"claseEstado(clave)\"", $html);
        // …que devuelve 'rechazada' (negativo) para el estado actual y nunca
        // 'done' para un paso fuera del flujo.
        $this->assertStringContainsString("return clave === 'rejected' ? 'rechazada' : 'active'", $html);
        $this->assertStringContainsString("if (clave === 'rejected') return 'idle'", $html);
        // La formula rota no puede volver.
        $this->assertStringNotContainsString(
            ".indexOf(form.status) > ['draft','sent','accepted'].indexOf(clave)", $html);
        // Y el estilo negativo existe y no es el verde de exito.
        $this->assertStringContainsString('.q-status-step.rechazada', $html);
        $this->assertStringContainsString('--peligro-fuerte', $html);
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
}
