<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Todo lo que entra al stock deja su asiento en el Kardex.
 *
 * Medido en ARIN: 753 de 755 productos con existencia no tenían un solo
 * movimiento. Ese stock apareció sin origen — entraba por la importación
 * masiva, que creaba el producto con `stock` ya puesto. El POS, los pedidos y
 * el conector del ERP sí registraban; el importador era la puerta que
 * faltaba, y con ella el inventario no podía auditarse: no había forma de
 * responder por qué un producto tiene 40 unidades.
 */
class InventarioAuditableTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['catalog.ver', 'catalog.editar', 'inventory.ver', 'inventory.editar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Inventario QA', 'slug' => 'inventario-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $this->usuario = User::factory()->create(['is_superadmin' => 1]);
        $this->actingAs($this->usuario)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    private function conModulo(string $key): void
    {
        $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function usuarioCon(array $permisos): User
    {
        $rol = Role::findOrCreate('inv_'.md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);

        return $u;
    }

    /** La existencia importada entra como movimiento, no como dato suelto. */
    public function test_la_importacion_masiva_deja_su_asiento_en_el_kardex(): void
    {
        $csv = "nombre,precio,stock,descripcion,sku\n"
             . "Taladro,199.90,7,Percutor,TAL-1\n"
             . "Cinta,5.50,20,Aislante,CIN-1\n";
        $archivo = UploadedFile::fake()->createWithContent('productos.csv', $csv);

        $this->post('/admin/imports/products', [
            'project_id' => $this->project->id,
            'file'       => $archivo,
        ])->assertRedirect();

        // Sin scopes: `Product` filtra por el proyecto activo de la sesion y
        // aqui se consulta el del import, que es el que interesa comprobar.
        $taladro = Product::withoutGlobalScopes()
            ->where('project_id', $this->project->id)->where('name', 'Taladro')->first();
        $this->assertNotNull($taladro, 'no se importó el producto');

        // El stock final es el del CSV...
        $this->assertSame(7, (int) $taladro->fresh()->stock);
        // ...pero llegó por un movimiento que dice de dónde salió.
        $movimiento = $taladro->movimientos()->first();
        $this->assertNotNull($movimiento, 'la importación no dejó rastro en el Kardex');
        $this->assertSame('importacion', $movimiento->reason);
        $this->assertSame(7, (int) $movimiento->quantity);
        $this->assertSame(7, (int) $movimiento->balance_after);
        $this->assertSame($this->usuario->id, $movimiento->user_id);
    }

    /** Un producto importado sin stock no inventa un movimiento de cero. */
    public function test_sin_existencia_no_hay_movimiento(): void
    {
        $csv = "nombre,precio,stock\nServicio,50.00,0\n";
        $this->post('/admin/imports/products', [
            'project_id' => $this->project->id,
            'file'       => UploadedFile::fake()->createWithContent('p.csv', $csv),
        ]);

        $servicio = Product::withoutGlobalScopes()
            ->where('project_id', $this->project->id)->where('name', 'Servicio')->first();
        $this->assertSame(0, $servicio->movimientos()->count());
    }

    /**
     * Inventario tiene módulo y permisos propios, pero las rutas se gateaban
     * con los del catálogo. Al migrar hay que aceptar ambos: 3 roles solo
     * tienen `catalog.ver` y un negocio no ha activado `inventory`.
     */
    public function test_quien_solo_tiene_permiso_de_catalogo_conserva_el_acceso(): void
    {
        $this->conModulo('catalog');
        $u = $this->usuarioCon(['catalog.ver']);

        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ])->get('/bixoadmin/inventario')->assertSuccessful();
    }

    /** Y el permiso propio ya vale por sí solo. */
    public function test_el_permiso_propio_de_inventario_da_acceso(): void
    {
        $this->conModulo('inventory');
        $u = $this->usuarioCon(['inventory.ver']);

        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ])->get('/bixoadmin/inventario')->assertSuccessful();
    }

    /** Sin ninguno de los dos permisos, no se entra. */
    public function test_sin_permiso_no_se_entra_al_inventario(): void
    {
        $this->conModulo('catalog');
        $u = $this->usuarioCon(['catalog.editar']);

        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ])->get('/bixoadmin/inventario')->assertForbidden();
    }
}
