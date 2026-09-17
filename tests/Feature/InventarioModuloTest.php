<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Modulo Inventario (app/Modules/Inventario): movimientos de stock, el
 * ledger (escritor unico del stock) y proveedores.
 *
 * Se vigila que sus pantallas se pinten con sus vistas bajo `inventario::`
 * (la de inventario ademas llama a InventoryLedger::etiqueta() desde el
 * namespace nuevo), y que nadie vuelva a crear ni importar estas clases por
 * su ruta vieja. Que el stock siga pasando por el ledger lo cubre
 * InventoryLedgerTest; aqui solo la frontera.
 */
class InventarioModuloTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['catalog.ver', 'inventory.ver', 'proveedores.ver'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio inventario',
            'slug'      => 'negocio-inventario',
            'is_active' => true,
        ]);
        // Inventario se gatea con el modulo de catalogo (no existe uno propio).
        $catalogo = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$catalogo->id => ['is_active' => true]]);

        Role::findOrCreate('inventario_test', 'web')->syncPermissions(['catalog.ver', 'inventory.ver', 'proveedores.ver']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Almacenero', 'spatie_role' => 'inventario_test', 'is_active' => 1,
        ]);
        $user->syncRoles(['inventario_test']);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);
    }

    public function test_inventario_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixoadmin/inventario')
            ->assertOk()
            ->assertViewIs('inventario::inventory.index');
    }

    public function test_proveedores_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixoadmin/company/proveedores')
            ->assertOk()
            ->assertViewIs('inventario::company.proveedores');
    }

    /** Frontera del modulo: estas clases viven SOLO en app/Modules/Inventario. */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/InventoryController.php',
            'app/Http/Controllers/ProveedorController.php',
            'app/Models/InventoryMovement.php',
            'app/Models/Proveedor.php',
            'app/Support/InventoryLedger.php',
            'resources/views/inventory',
            'resources/views/company/proveedores.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Inventario es su unico sitio.");
        }

        $patron = '/\bApp\\\\(Models\\\\(InventoryMovement|Proveedor)\b|Support\\\\InventoryLedger\b|Http\\\\Controllers\\\\(Inventory|Proveedor)Controller\b)/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Inventario/') || $rel === 'tests/Feature/InventarioModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Inventario por su ruta vieja:\n" . implode("\n", $infractores));
    }
}
