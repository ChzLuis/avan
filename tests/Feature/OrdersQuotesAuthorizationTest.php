<?php

namespace Tests\Feature;

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
 * Autorizacion de Pedidos y Cotizaciones.
 *
 * Regla que se protege: *.ver solo puede autorizar lectura. Ningun POST, PUT,
 * PATCH o DELETE debe quedar autorizado unicamente por orders.ver / quotes.ver.
 *
 * Antes del hotfix Route::resource aplicaba can:orders.ver a los 7 verbos, asi
 * que un usuario de solo lectura podia crear, editar y BORRAR pedidos.
 */
class OrdersQuotesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Project $otroProject;
    private Order $order;
    private Quote $quote;
    private Order $orderAjeno;
    private Quote $quoteAjeno;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'orders.ver', 'orders.crear', 'orders.editar', 'orders.eliminar',
            'quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
        ] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project     = $this->crearProject('proyecto-a');
        $this->otroProject = $this->crearProject('proyecto-b');

        $this->order      = $this->crearOrder($this->project);
        $this->quote      = $this->crearQuote($this->project);
        $this->orderAjeno = $this->crearOrder($this->otroProject);
        $this->quoteAjeno = $this->crearQuote($this->otroProject);
    }

    private function crearProject(string $slug): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name'     => 'Negocio '.$slug,
            'slug'     => $slug,
            'is_active' => true,
        ]);

        foreach (['orders', 'quotes'] as $key) {
            $module = Module::firstOrCreate(['key' => $key], ['name' => ucfirst($key), 'is_active' => true]);
            $project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
        }

        return $project;
    }

    private function crearOrder(Project $project): Order
    {
        return Order::create([
            'project_id' => $project->id, 'client_name' => 'Cliente', 'status' => 'pending', 'total' => 100,
        ]);
    }

    private function crearQuote(Project $project): Quote
    {
        return Quote::create([
            'project_id' => $project->id, 'client_name' => 'Cliente', 'status' => 'draft', 'total' => 100,
        ]);
    }

    /**
     * Crea un usuario real del panel: User + ProjectMember + Employee.spatie_role.
     *
     * Los tres son necesarios. ProjectMember porque CheckProjectMember valida
     * contra project_members (NO contra employees), y Employee.spatie_role
     * porque SetActiveProject hace syncRoles() en cada peticion y borraria
     * cualquier rol asignado solo via model_has_roles.
     */
    private function usuarioConRol(string $rol, array $permisos): User
    {
        Role::findOrCreate($rol, 'web')->syncPermissions($permisos);

        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Empleado '.$rol, 'spatie_role' => $rol, 'is_active' => 1,
        ]);
        $user->syncRoles([$rol]);

        return $user;
    }

    private function soloLectura(): User
    {
        return $this->usuarioConRol('qa_lectura_test', ['orders.ver', 'quotes.ver']);
    }

    private function conEscritura(): User
    {
        return $this->usuarioConRol('gerente_test', [
            'orders.ver', 'orders.crear', 'orders.editar', 'orders.eliminar',
            'quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
        ]);
    }

    private function comoUsuario(User $user): self
    {
        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);
        return $this;
    }

    // ─── Lectura permitida ────────────────────────────────────────────────

    /** @dataProvider rutasDeLectura */
    public function test_lector_puede_leer(string $ruta): void
    {
        $this->comoUsuario($this->soloLectura());
        $this->get($this->resolver($ruta))->assertSuccessful();
    }

    public static function rutasDeLectura(): array
    {
        return [
            'listado pedidos'      => ['/orders'],
            'detalle pedido'       => ['/orders/{order}'],
            'eventos JSON'         => ['/orders/{order}/events'],
            'etiqueta'             => ['/orders/{order}/tag'],
            'exportar CSV'         => ['/orders-export'],
            'listado cotizaciones' => ['/quotes'],
            'detalle cotizacion'   => ['/quotes/{quote}'],
        ];
    }

    // ─── Escritura denegada al lector ─────────────────────────────────────

    /** @dataProvider rutasMutadoras */
    public function test_lector_recibe_403_en_toda_operacion_mutadora(string $verbo, string $ruta): void
    {
        $this->comoUsuario($this->soloLectura());
        $this->json($verbo, $this->resolver($ruta), $this->cargaUtil($ruta))->assertForbidden();
    }

    public static function rutasMutadoras(): array
    {
        return [
            'crear pedido'          => ['POST',   '/orders'],
            'editar pedido'         => ['PUT',    '/orders/{order}'],
            'editar pedido PATCH'   => ['PATCH',  '/orders/{order}'],
            'BORRAR pedido'         => ['DELETE', '/orders/{order}'],
            'registrar pago'        => ['POST',   '/orders/{order}/pay'],
            'emitir comprobante'    => ['POST',   '/orders/{order}/issue-document'],
            'marcar WhatsApp'       => ['POST',   '/orders/{order}/wa-sent'],
            'accion WhatsApp'       => ['POST',   '/orders/{order}/wa-action'],
            'entrega WhatsApp'      => ['POST',   '/orders/{order}/wa-delivery'],
            'estado lavanderia'     => ['POST',   '/orders/{order}/laundry-status'],
            'crear cotizacion'      => ['POST',   '/quotes'],
            'editar cotizacion'     => ['PUT',    '/quotes/{quote}'],
            'editar cotiz. PATCH'   => ['PATCH',  '/quotes/{quote}'],
            'BORRAR cotizacion'     => ['DELETE', '/quotes/{quote}'],
            'editar completa'       => ['PUT',    '/quotes/{quote}/full'],
            'enviar cotizacion'     => ['POST',   '/quotes/{quote}/send'],
            'duplicar cotizacion'   => ['POST',   '/quotes/{quote}/duplicate'],
            'convertir en pedido'   => ['POST',   '/quotes/{quote}/convert'],
        ];
    }

    /**
     * El acuse de lectura es la unica escritura que un lector puede hacer: lo
     * dispara la propia vista al abrir la cotizacion. Exigir quotes.editar
     * daria 403 en consola a todo lector legitimo.
     */
    public function test_lector_puede_marcar_cotizacion_como_vista(): void
    {
        $this->comoUsuario($this->soloLectura());
        $this->postJson('/quotes/'.$this->quote->id.'/seen')->assertSuccessful();
    }

    // ─── El rol autorizado no quedo bloqueado ─────────────────────────────

    public function test_rol_con_escritura_conserva_sus_operaciones(): void
    {
        $this->comoUsuario($this->conEscritura());

        $this->postJson('/orders', [
            'client_name' => 'Nuevo',
            'items' => [['name' => 'Camisa', 'price' => 25, 'quantity' => 2]],
        ])->assertSuccessful();

        $this->putJson('/orders/'.$this->order->id, ['status' => 'process'])
            ->assertSuccessful();

        $this->postJson('/quotes', [
            'client_name' => 'Nuevo',
            'items' => [['description' => 'Servicio', 'price' => 25, 'quantity' => 2]],
        ])->assertSuccessful();

        $this->deleteJson('/quotes/'.$this->quote->id)
            ->assertSuccessful();
    }

    public function test_rol_con_escritura_puede_borrar_pedido(): void
    {
        $this->comoUsuario($this->conEscritura());
        $this->deleteJson('/orders/'.$this->order->id)->assertSuccessful();
        $this->assertDatabaseMissing('orders', ['id' => $this->order->id]);
    }

    // ─── El aislamiento por proyecto sigue vigente ────────────────────────

    public function test_lector_no_alcanza_datos_de_otro_proyecto(): void
    {
        $this->comoUsuario($this->soloLectura());

        $this->sinAcceso($this->get('/orders/'.$this->orderAjeno->id));
        $this->sinAcceso($this->get('/quotes/'.$this->quoteAjeno->id));
        $this->sinAcceso($this->getJson('/orders/'.$this->orderAjeno->id.'/events'));
    }

    public function test_rol_con_escritura_tampoco_alcanza_otro_proyecto(): void
    {
        $this->comoUsuario($this->conEscritura());

        $this->sinAcceso($this->get('/orders/'.$this->orderAjeno->id));
        $this->sinAcceso($this->putJson('/orders/'.$this->orderAjeno->id, ['status' => 'process']));
        $this->sinAcceso($this->deleteJson('/quotes/'.$this->quoteAjeno->id));

        $this->assertDatabaseHas('quotes', ['id' => $this->quoteAjeno->id]);
    }

    public function test_el_listado_solo_trae_pedidos_del_proyecto_activo(): void
    {
        $this->comoUsuario($this->soloLectura());
        $respuesta = $this->get('/orders')->assertSuccessful();

        $pedidos = $respuesta->viewData('orders');
        $this->assertTrue($pedidos->contains('id', $this->order->id));
        $this->assertFalse($pedidos->contains('id', $this->orderAjeno->id));
    }

    // ─── Utilidades ───────────────────────────────────────────────────────

    /**
     * El aislamiento entre proyectos tiene dos capas y la primera gana:
     *
     *  1. HasProjectScope anade un scope global por session('active_project_id'),
     *     asi que el route model binding de otro proyecto ni siquiera resuelve
     *     -> 404. Es mas fuerte que 403: no confirma que el ID exista.
     *  2. Si ese scope se retirara, seguiria el abort_unless(project_id) -> 403.
     *
     * Se aceptan ambos para que la prueba mida la propiedad de seguridad (no hay
     * acceso) y no el codigo concreto con que se niega.
     */
    private function sinAcceso(\Illuminate\Testing\TestResponse $respuesta): void
    {
        $this->assertContains(
            $respuesta->getStatusCode(),
            [403, 404],
            'Se esperaba 403 o 404 al alcanzar un recurso de otro proyecto, llego '.$respuesta->getStatusCode()
        );
    }

    private function resolver(string $ruta): string
    {
        return str_replace(
            ['{order}', '{quote}'],
            [(string) $this->order->id, (string) $this->quote->id],
            $ruta
        );
    }

    /** Cuerpo minimo valido para que el 403 provenga del permiso y no de la validacion. */
    private function cargaUtil(string $ruta): array
    {
        return match (true) {
            str_contains($ruta, 'wa-sent')        => ['template' => 'saludo', 'to' => '51999999999'],
            str_contains($ruta, 'laundry-status') => ['status' => 'washing'],
            str_contains($ruta, 'pay')            => ['amount' => 10, 'payment_method' => 'efectivo'],
            str_contains($ruta, '/orders')        => ['client_name' => 'X', 'total' => 10],
            str_contains($ruta, '/quotes')        => ['client_name' => 'X', 'total' => 10],
            default                               => [],
        };
    }
}
