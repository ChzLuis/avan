<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Autorizacion del Catalogo (productos, servicios y categorias).
 *
 * Regla que se protege: catalog.ver solo autoriza LECTURA. Ningun POST, PUT,
 * PATCH o DELETE puede quedar autorizado unicamente por ese permiso.
 *
 * Antes de este cambio el grupo entero de /products llevaba un unico
 * can:catalog.ver, asi que un usuario de solo lectura podia crear, editar,
 * importar, aplicar acciones masivas y hasta vaciar el catalogo completo con
 * DELETE /products/purge-all. Es el mismo hueco que 9b2bcc5 cerro en Pedidos
 * y Cotizaciones; Catalogo se habia quedado fuera.
 */
class CatalogAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Product $product;
    private Service $service;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'catalog.ver', 'catalog.crear', 'catalog.editar',
            'catalog.eliminar', 'catalog.importar', 'catalog.resenas',
        ] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project  = $this->crearProject('catalogo-a');
        $this->category = Category::create(['project_id' => $this->project->id, 'name' => 'General']);
        $this->product  = Product::create([
            'project_id' => $this->project->id, 'name' => 'Producto', 'price' => 10, 'stock' => 5,
        ]);
        $this->service  = Service::create([
            'project_id' => $this->project->id, 'name' => 'Servicio', 'price' => 20,
        ]);
    }

    private function crearProject(string $slug): Project
    {
        $project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio '.$slug,
            'slug'      => $slug,
            'is_active' => true,
        ]);

        $module = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catalogo', 'is_active' => true]);
        $project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);

        return $project;
    }

    /** Igual que en OrdersQuotesAuthorizationTest: User + ProjectMember + Employee.spatie_role. */
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
        return $this->usuarioConRol('catalogo_lectura_test', ['catalog.ver']);
    }

    private function conEscritura(): User
    {
        return $this->usuarioConRol('catalogo_gerente_test', [
            'catalog.ver', 'catalog.crear', 'catalog.editar',
            'catalog.eliminar', 'catalog.importar', 'catalog.resenas',
        ]);
    }

    private function comoUsuario(User $user): self
    {
        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);
        return $this;
    }

    private function resolver(string $ruta): string
    {
        return '/bixoadmin'.str_replace(
            ['{product}', '{service}', '{category}'],
            [$this->product->id, $this->service->id, $this->category->id],
            $ruta
        );
    }

    // ─── Lectura: sigue abierta para catalog.ver ──────────────────────────

    /** @dataProvider rutasDeLectura */
    public function test_lector_puede_leer(string $ruta): void
    {
        $this->comoUsuario($this->soloLectura());
        $this->get($this->resolver($ruta))->assertSuccessful();
    }

    public static function rutasDeLectura(): array
    {
        return [
            'listado productos'  => ['/products'],
            'listado categorias' => ['/categories'],
            'listado servicios'  => ['/services'],
            'exportar productos' => ['/products/export'],
        ];
    }

    // ─── Escritura: prohibida para quien solo tiene catalog.ver ───────────

    /** @dataProvider rutasDeEscritura */
    public function test_lector_no_puede_escribir(string $verbo, string $ruta): void
    {
        $this->comoUsuario($this->soloLectura());
        $this->json($verbo, $this->resolver($ruta), $this->cuerpoMinimo($ruta))->assertForbidden();
    }

    public static function rutasDeEscritura(): array
    {
        return [
            'crear producto'       => ['POST',   '/products'],
            'editar producto'      => ['PUT',    '/products/{product}'],
            'borrar producto'      => ['DELETE', '/products/{product}'],
            'duplicar producto'    => ['POST',   '/products/{product}/duplicate'],
            'importar productos'   => ['POST',   '/products/import'],
            'reordenar productos'  => ['POST',   '/products/reorder'],
            'accion masiva'        => ['POST',   '/products/bulk-action'],
            'crear categoria'      => ['POST',   '/categories'],
            'editar categoria'     => ['PUT',    '/categories/{category}'],
            'borrar categoria'     => ['DELETE', '/categories/{category}'],
            'crear servicio'       => ['POST',   '/services'],
            'editar servicio'      => ['PUT',    '/services/{service}'],
            'borrar servicio'      => ['DELETE', '/services/{service}'],
        ];
    }

    /**
     * Quien SI tiene permiso de escritura no debe verse bloqueado por autorizacion.
     *
     * @dataProvider rutasDeEscritura
     */
    public function test_usuario_con_escritura_no_recibe_403(string $verbo, string $ruta): void
    {
        $this->comoUsuario($this->conEscritura());
        $respuesta = $this->json($verbo, $this->resolver($ruta), $this->cuerpoMinimo($ruta));

        $this->assertNotSame(403, $respuesta->status(), "{$verbo} {$ruta} devolvio 403 a un usuario con permiso de escritura.");
    }

    /**
     * Vaciar el catalogo sigue siendo exclusivo de superadmin.
     *
     * Se comprueba aparte porque purgeAll() tiene su propio abort_unless(
     * is_superadmin) ademas del permiso de ruta: ni siquiera un usuario con
     * catalog.eliminar puede vaciarlo, y eso debe seguir asi.
     */
    public function test_vaciar_catalogo_es_solo_de_superadmin(): void
    {
        foreach ([$this->soloLectura(), $this->conEscritura()] as $usuario) {
            $this->comoUsuario($usuario)
                ->json('DELETE', $this->resolver('/products/purge-all'), ['confirm_slug' => $this->project->slug])
                ->assertForbidden();
        }

        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
    }

    /**
     * La accion masiva 'delete' exige catalog.eliminar aunque la ruta pida solo
     * catalog.editar: el permiso depende del payload y no se puede resolver en
     * el middleware.
     */
    public function test_accion_masiva_delete_exige_permiso_de_borrado(): void
    {
        $editorSinBorrado = $this->usuarioConRol('catalogo_editor_test', ['catalog.ver', 'catalog.editar']);

        $this->comoUsuario($editorSinBorrado)
            ->json('POST', $this->resolver('/products/bulk-action'), [
                'ids' => [$this->product->id], 'action' => 'delete',
            ])->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
    }

    /** Ese mismo editor SI puede aplicar una accion masiva que no borra. */
    public function test_accion_masiva_no_destructiva_sigue_permitida_al_editor(): void
    {
        $editorSinBorrado = $this->usuarioConRol('catalogo_editor2_test', ['catalog.ver', 'catalog.editar']);

        $this->comoUsuario($editorSinBorrado)
            ->json('POST', $this->resolver('/products/bulk-action'), [
                'ids' => [$this->product->id], 'action' => 'unavailable',
            ])->assertSuccessful();

        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'is_available' => 0]);
    }

    private function cuerpoMinimo(string $ruta): array
    {
        return match (true) {
            str_contains($ruta, 'bulk-action') => ['ids' => [$this->product->id], 'action' => 'unavailable'],
            str_contains($ruta, 'reorder')     => ['ids' => [$this->product->id]],
            str_contains($ruta, '/products')   => ['name' => 'Nuevo', 'price' => 10],
            str_contains($ruta, '/services')   => ['name' => 'Nuevo servicio', 'price' => 10],
            str_contains($ruta, '/categories') => ['name' => 'Nueva categoria'],
            default => [],
        };
    }
}
