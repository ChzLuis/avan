<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Modules\Catalogo\Models\Category;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Inventario\Models\Proveedor;
use App\Modules\Ventas\Models\Quote;
use App\Modules\Catalogo\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASE 8 — Aislamiento entre negocios.
 *
 * Regla del plan (§3.2): permiso y proyecto son condiciones INDEPENDIENTES.
 * Tener permiso no puede permitir nunca operar sobre recursos de otro negocio.
 *
 * El usuario de estas pruebas tiene TODOS los permisos en su proyecto. Lo que se
 * comprueba no es si puede, sino si el sistema le deja alcanzar datos ajenos.
 * Cualquier respuesta distinta de 403/404 sobre un recurso del otro negocio es
 * una fuga.
 */
class ProjectIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const TODOS = [
        'catalog.ver', 'catalog.crear', 'catalog.editar', 'catalog.eliminar', 'catalog.importar',
        'orders.ver', 'orders.crear', 'orders.editar', 'orders.eliminar', 'orders.cancelar',
        'quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
        'clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar',
        'agenda.ver', 'agenda.crear', 'agenda.editar',
        'proveedores.ver', 'proveedores.editar',
        'settings.ver', 'settings.negocio', 'settings.diseno', 'settings.catalogos',
        'settings.pagos', 'settings.qr',
        'roles.ver', 'roles.gestionar', 'reports.ver',
    ];

    private Project $mio;
    private Project $ajeno;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TODOS as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->mio   = $this->crearProyecto('mi-negocio');
        $this->ajeno = $this->crearProyecto('negocio-ajeno');

        $this->actingAs($this->usuarioTodopoderoso())
             ->withSession(['active_project_id' => $this->mio->id]);
    }

    private function crearProyecto(string $slug): Project
    {
        $p = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio '.$slug,
            'slug'      => $slug,
            'is_active' => true,
        ]);

        foreach (['catalog', 'orders', 'quotes', 'agenda', 'clients', 'settings'] as $k) {
            $m = Module::firstOrCreate(['key' => $k], ['name' => ucfirst($k), 'is_active' => true]);
            $p->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        return $p;
    }

    /** Usuario con todos los permisos, pero SOLO miembro de su propio proyecto. */
    private function usuarioTodopoderoso(): User
    {
        Role::findOrCreate('todopoderoso_test', 'web')->syncPermissions(self::TODOS);

        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->mio->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->mio->id, 'user_id' => $u->id,
            'name' => 'Empleado', 'spatie_role' => 'todopoderoso_test', 'is_active' => 1,
        ]);
        $u->syncRoles(['todopoderoso_test']);

        return $u;
    }

    /** Crea el mismo tipo de registro en el proyecto ajeno. */
    private function ajeno(string $tipo)
    {
        return match ($tipo) {
            'product'  => Product::create(['project_id' => $this->ajeno->id, 'name' => 'Producto ajeno', 'price' => 10, 'stock' => 5]),
            'category' => Category::create(['project_id' => $this->ajeno->id, 'name' => 'Categoría ajena']),
            'service'  => Service::create(['project_id' => $this->ajeno->id, 'name' => 'Servicio ajeno', 'price' => 20]),
            'client'   => Client::create(['project_id' => $this->ajeno->id, 'name' => 'Cliente ajeno']),
            'order'    => Order::create(['project_id' => $this->ajeno->id, 'client_name' => 'X', 'status' => 'pending', 'total' => 10]),
            'quote'    => Quote::create(['project_id' => $this->ajeno->id, 'client_name' => 'X', 'status' => 'draft', 'total' => 10]),
            'proveedor'=> Proveedor::create(['project_id' => $this->ajeno->id, 'name' => 'Proveedor ajeno', 'is_active' => true]),
            'cita'     => Appointment::create([
                'project_id' => $this->ajeno->id, 'client_name' => 'X',
                'date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'pending',
            ]),
        };
    }

    /**
     * Escribir sobre un recurso de otro negocio debe fallar SIEMPRE.
     *
     * @dataProvider escriturasCruzadas
     */
    public function test_no_puedo_escribir_en_otro_negocio(string $tipo, string $verbo, string $plantilla): void
    {
        $registro = $this->ajeno($tipo);
        $ruta = str_replace('{id}', (string) $registro->id, $plantilla);

        $respuesta = $this->json($verbo, $ruta, ['name' => 'Secuestrado', 'price' => 1]);

        $this->assertContains(
            $respuesta->status(),
            [403, 404],
            "{$verbo} {$ruta} respondió {$respuesta->status()} sobre un registro de OTRO negocio."
        );
    }

    public static function escriturasCruzadas(): array
    {
        return [
            'editar producto ajeno'   => ['product',   'PUT',    '/bixoadmin/products/{id}'],
            'borrar producto ajeno'   => ['product',   'DELETE', '/bixoadmin/products/{id}'],
            'duplicar producto ajeno' => ['product',   'POST',   '/bixoadmin/products/{id}/duplicate'],
            'editar categoría ajena'  => ['category',  'PUT',    '/bixoadmin/categories/{id}'],
            'borrar categoría ajena'  => ['category',  'DELETE', '/bixoadmin/categories/{id}'],
            'editar servicio ajeno'   => ['service',   'PUT',    '/bixoadmin/services/{id}'],
            'borrar servicio ajeno'   => ['service',   'DELETE', '/bixoadmin/services/{id}'],
            'editar cliente ajeno'    => ['client',    'PUT',    '/bixoadmin/clients/{id}'],
            'editar pedido ajeno'     => ['order',     'PUT',    '/bixoadmin/orders/{id}'],
            'borrar pedido ajeno'     => ['order',     'DELETE', '/bixoadmin/orders/{id}'],
            'editar cotización ajena' => ['quote',     'PUT',    '/bixoadmin/quotes/{id}'],
            'editar proveedor ajeno'  => ['proveedor', 'PUT',    '/bixoadmin/company/proveedores/{id}'],
            'borrar proveedor ajeno'  => ['proveedor', 'DELETE', '/bixoadmin/company/proveedores/{id}'],
            'editar cita ajena'       => ['cita',      'PUT',    '/bixoadmin/appointments/{id}'],
            'borrar cita ajena'       => ['cita',      'DELETE', '/bixoadmin/appointments/{id}'],
        ];
    }

    /**
     * Leer un recurso de otro negocio tampoco debe poderse.
     *
     * @dataProvider lecturasCruzadas
     */
    public function test_no_puedo_leer_datos_de_otro_negocio(string $tipo, string $plantilla): void
    {
        $registro = $this->ajeno($tipo);
        $ruta = str_replace('{id}', (string) $registro->id, $plantilla);

        $respuesta = $this->get($ruta);

        $this->assertContains(
            $respuesta->status(),
            [403, 404],
            "GET {$ruta} respondió {$respuesta->status()} sobre un registro de OTRO negocio."
        );
    }

    public static function lecturasCruzadas(): array
    {
        return [
            'ver pedido ajeno'     => ['order',   '/bixoadmin/orders/{id}'],
            'ver cotización ajena' => ['quote',   '/bixoadmin/quotes/{id}'],
            'kardex ajeno'         => ['product', '/bixoadmin/inventario/{id}/kardex'],
        ];
    }

    /** El listado solo puede traer registros del proyecto activo. */
    public function test_los_listados_no_filtran_datos_ajenos(): void
    {
        $this->ajeno('product');
        Product::create(['project_id' => $this->mio->id, 'name' => 'Producto propio', 'price' => 10]);

        $html = $this->get('/bixoadmin/products')->assertSuccessful()->getContent();

        $this->assertStringContainsString('Producto propio', $html);
        $this->assertStringNotContainsString('Producto ajeno', $html, 'El listado muestra productos de otro negocio.');
    }

    /**
     * Desactivar el negocio de otro manipulando el id de la URL.
     * El caso 3 del plan: dueño de un proyecto tocando la administración de otro.
     */
    public function test_no_puedo_desactivar_el_negocio_ajeno(): void
    {
        $this->json('PATCH', '/bixoadmin/settings/'.$this->ajeno->id.'/toggle')
            ->assertStatus(403);

        $this->assertTrue((bool) $this->ajeno->fresh()->is_active, 'Se desactivó un negocio ajeno.');
    }

    public function test_no_puedo_cambiar_los_modulos_del_negocio_ajeno(): void
    {
        $this->json('POST', '/bixoadmin/settings/'.$this->ajeno->id.'/modules', ['modules' => []])
            ->assertStatus(403);

        $this->assertGreaterThan(0, $this->ajeno->fresh()->modules()->count(), 'Se vaciaron los módulos de un negocio ajeno.');
    }

    /**
     * Copiar la configuración de una tienda ajena.
     *
     * Es el único endpoint que recibe un id de proyecto DISTINTO del activo por
     * diseño, así que se fija aquí: solo puede copiarse desde un proyecto propio.
     */
    public function test_no_puedo_copiar_la_tienda_de_otro_negocio(): void
    {
        $this->json('POST', '/bixoadmin/settings/builder/copy', [
            'source_id' => $this->ajeno->id,
            'parts'     => ['apariencia'],
        ])->assertStatus(403);
    }

    /** Registrar un movimiento de stock sobre un producto de otro negocio. */
    public function test_no_puedo_mover_el_stock_de_otro_negocio(): void
    {
        $producto = $this->ajeno('product');

        $respuesta = $this->json('POST', '/bixoadmin/inventario/movimiento', [
            'product_id' => $producto->id,
            'reason'     => 'merma',
            'quantity'   => 5,
        ]);

        $this->assertContains($respuesta->status(), [403, 404, 422]);

        $this->assertSame(5, (int) $producto->fresh()->stock, 'Se movió el stock de un producto ajeno.');
    }
}
