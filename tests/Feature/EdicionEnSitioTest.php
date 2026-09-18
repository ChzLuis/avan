<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Edicion en el sitio, confirmaciones y busqueda global.
 *
 * Tres cosas que se pidieron juntas y que comparten una idea: la interfaz
 * deja de pedir permiso para trabajar (se escribe donde esta el dato) pero
 * sigue pidiendolo para lo irreversible (se confirma antes de borrar).
 */
class EdicionEnSitioTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
                  'orders.ver', 'clients.ver', 'catalog.ver',
                  'view-quotes', 'manage-quotes'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Edicion QA', 'slug' => 'edicion-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes', 'catalog'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function entrar(array $permisos): User
    {
        $rol = Role::findOrCreate('ed_'.md5(implode(',', $permisos)), 'web');
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
            'project_id' => $this->project->id, 'client_name' => 'Cliente QA',
            'status' => 'sent', 'total' => '100.00', 'token' => str()->random(24),
        ], $attrs));
        $q->items()->create(['description' => 'X', 'price' => '100.00', 'quantity' => 1, 'discount' => 0]);

        return $q;
    }

    /**
     * Los datos se escriben donde se leen. Lo que desaparece es el modo
     * aparte: no hay un boton que abrir antes de poder teclear.
     */
    public function test_los_campos_se_editan_donde_estan(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // Cliente y Condiciones son campos, no parrafos de solo lectura.
        $this->assertStringContainsString('x-model="form.client_name"', $html);
        $this->assertStringContainsString('x-model="form.payment_condition"', $html);
        // Y se guardan solos al salir del campo.
        $this->assertStringContainsString('@change="guardarCampo()"', $html);

        // El interruptor de modo ya no existe en ninguna de sus formas.
        foreach (['modo.cliente', 'modo.condiciones', 'modo.productos', '>Editar<'] as $residuo) {
            $this->assertStringNotContainsString($residuo, $html, "resto del modo de edicion: {$residuo}");
        }
    }

    /**
     * Un documento convertido ya engendro un pedido: el servidor rechaza
     * reescribirlo con 422, asi que la interfaz tampoco puede ofrecerlo.
     */
    public function test_una_convertida_no_se_edita_en_el_sitio(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote(['status' => 'converted']);

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // La condicion de edicion incluye el estado, no solo el permiso.
        $this->assertStringContainsString('get editable() { return this.puede.editar && !this.esConvertida; }', $html);
        $this->assertStringContainsString(':readonly="!editable"', $html);
        $this->assertStringContainsString("q-inline-off", $html);
    }

    /** La grilla siempre termina en una fila vacia lista para escribir. */
    public function test_la_grilla_deja_una_fila_libre_al_final(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        $this->assertStringContainsString('asegurarFilaVacia()', $html);
        $this->assertStringContainsString('alEscribirLinea(i, $event.target.value)', $html);
        // Y sugiere del catalogo mientras se escribe.
        $this->assertStringContainsString('usarProducto(i, pr)', $html);
    }

    /**
     * Lo irreversible se confirma con un dialogo propio, que dice QUE se va a
     * borrar. El confirm() del navegador no puede contarlo y ademas no se
     * puede recorrer con teclado como el resto de la interfaz.
     */
    public function test_las_acciones_irreversibles_piden_confirmacion(): void
    {
        $this->entrar(['quotes.ver', 'quotes.editar', 'quotes.eliminar']);
        $this->quote();

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        // Ni una sola llamada al confirm() del navegador. Se busca la forma
        // en que se invoca —con su argumento— y no la palabra suelta, que
        // aparece legitimamente en los comentarios que explican por que se
        // retiro.
        $this->assertDoesNotMatchRegularExpression('/(?<![\w.])confirm\s*\(\s*[\'"`]/', $html);

        // Y el dialogo propio es accesible.
        $this->assertStringContainsString('aria-labelledby="tituloConfirmar"', $html);
        $this->assertStringContainsString('x-trap.noscroll="confirmar.abierto"', $html);
        $this->assertStringContainsString('ejecutarConfirmacion()', $html);

        // Cubriendo las cuatro acciones que lo necesitan.
        foreach (['Eliminar cotización', 'Quitar esta línea', 'Reenviar al cliente', 'Eliminar '] as $caso) {
            $this->assertStringContainsString($caso, $html, "sin confirmacion para: {$caso}");
        }
    }

    /** La busqueda global encuentra en varias secciones y agrupa. */
    public function test_la_busqueda_global_recorre_varias_secciones(): void
    {
        $this->entrar(['quotes.ver', 'clients.ver', 'orders.ver']);
        Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería Molina', 'phone' => '999111222']);
        $this->quote(['client_name' => 'Ferretería Molina']);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Ferretería Molina',
            'status' => 'pending', 'total' => '50.00']);

        $grupos = $this->getJson('/bixosales/buscar?q=molina')->assertSuccessful()->json('grupos');

        $titulos = array_column($grupos, 'titulo');
        $this->assertContains('Clientes', $titulos);
        $this->assertContains('Cotizaciones', $titulos);
        $this->assertContains('Pedidos', $titulos);
    }

    /**
     * El buscador NO es una puerta trasera: cada grupo exige el permiso de su
     * seccion. Sin acceso a Cotizaciones, no aparecen por aqui.
     */
    public function test_la_busqueda_respeta_los_permisos_de_cada_seccion(): void
    {
        $this->entrar(['clients.ver']);
        Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería Molina']);
        $this->quote(['client_name' => 'Ferretería Molina']);

        $grupos = $this->getJson('/bixosales/buscar?q=molina')->assertSuccessful()->json('grupos');

        $titulos = array_column($grupos, 'titulo');
        $this->assertContains('Clientes', $titulos);
        $this->assertNotContains('Cotizaciones', $titulos);
    }

    /** Ni una puerta lateral entre negocios. */
    public function test_la_busqueda_no_cruza_negocios(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-busca', 'category' => 'retail', 'is_active' => true,
        ]);
        Client::create(['project_id' => $otro->id, 'name' => 'Ferretería Molina']);

        $this->entrar(['clients.ver']);

        $grupos = $this->getJson('/bixosales/buscar?q=molina')->assertSuccessful()->json('grupos');

        $this->assertSame([], $grupos);
    }

    /** Un termino demasiado corto no dispara una consulta a cinco tablas. */
    public function test_la_busqueda_no_responde_a_una_sola_letra(): void
    {
        $this->entrar(['clients.ver']);
        Client::create(['project_id' => $this->project->id, 'name' => 'Molina']);

        $this->assertSame([], $this->getJson('/bixosales/buscar?q=m')->json('grupos'));
    }

    /**
     * Los pedidos se numeran por negocio, igual que las cotizaciones: el `id`
     * es global y el primer pedido de un cliente nuevo salia como PED-187.
     */
    public function test_los_pedidos_se_numeran_por_negocio(): void
    {
        $primero = Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'total' => '10.00']);
        $segundo = Order::create(['project_id' => $this->project->id, 'client_name' => 'B',
            'status' => 'pending', 'total' => '20.00']);

        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otro', 'slug' => 'otro-ped', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajeno = Order::create(['project_id' => $otro->id, 'client_name' => 'C',
            'status' => 'pending', 'total' => '30.00']);

        $this->assertSame('PED-00001', $primero->etiqueta);
        $this->assertSame('PED-00002', $segundo->etiqueta);
        // El otro negocio arranca su propia secuencia, no continua la ajena.
        $this->assertSame('PED-00001', $ajeno->etiqueta);
    }

    /** Reparto no se publica hasta estar terminado. */
    public function test_reparto_no_aparece_en_el_menu_por_tener_pedidos_de_delivery(): void
    {
        Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'total' => '10.00', 'order_type' => 'delivery']);

        $modulos = \App\Support\ModulosPortal::liberados($this->project->fresh());

        $this->assertFalse($modulos['reparto'], 'Reparto sigue anunciandose sin estar terminado');
    }
}
