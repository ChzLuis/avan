<?php

namespace Tests\Unit;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Modules\Ventas\Support\QuoteAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * QuoteAbilities resuelve en un solo sitio lo que la vista puede enseñar.
 *
 * Lo que se prueba aqui es la equivalencia con las rutas: el backend protege
 * con project.can:permisoB|permisoLegacy (OR), asi que un usuario del universo
 * heredado debe poder tanto como uno del canonico. Si esto se desincroniza,
 * aparecen botones que terminan en 403.
 */
class QuoteAbilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
                  'view-quotes', 'manage-quotes'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    private function proyecto(bool $conModuloPedidos = true): Project
    {
        $project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Abilities',
            'slug'      => 'abilities-' . ($conModuloPedidos ? 'con' : 'sin'),
            'category'  => 'retail',
            'is_active' => true,
        ]);

        $claves = $conModuloPedidos ? ['quotes', 'orders'] : ['quotes'];
        foreach ($claves as $key) {
            $module = Module::firstOrCreate(['key' => $key], ['name' => ucfirst($key), 'is_active' => true]);
            $project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
        }

        return $project;
    }

    private function usuarioCon(array $permisos): User
    {
        $rol = Role::findOrCreate('rol_' . md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
        $user = User::factory()->create(['is_superadmin' => 0]);
        $user->syncRoles([$rol->name]);

        return $user->fresh();
    }

    public function test_sin_usuario_no_puede_nada(): void
    {
        $capacidades = QuoteAbilities::para(null, $this->proyecto());

        foreach (['ver', 'crear', 'editar', 'eliminar', 'convertir'] as $accion) {
            $this->assertFalse($capacidades[$accion], "sin usuario, '{$accion}' debe ser false");
        }
    }

    public function test_universo_canonico_B(): void
    {
        $user = $this->usuarioCon(['quotes.ver', 'quotes.editar']);
        $capacidades = QuoteAbilities::para($user, $this->proyecto());

        $this->assertTrue($capacidades['ver']);
        $this->assertTrue($capacidades['editar']);
        $this->assertTrue($capacidades['convertir'], 'quotes.editar + modulo orders habilita convertir');
        $this->assertFalse($capacidades['crear']);
        $this->assertFalse($capacidades['eliminar']);
    }

    public function test_universo_heredado_A_puede_lo_mismo(): void
    {
        // El caso que motiva esta clase: hasta F1c, la ruta de convertir exigia
        // 'quotes.editar' puro, asi que un usuario con el permiso heredado
        // podia editar y enviar pero NO convertir.
        $user = $this->usuarioCon(['view-quotes', 'manage-quotes']);
        $capacidades = QuoteAbilities::para($user, $this->proyecto());

        $this->assertTrue($capacidades['ver']);
        $this->assertTrue($capacidades['crear']);
        $this->assertTrue($capacidades['editar']);
        $this->assertTrue($capacidades['eliminar']);
        $this->assertTrue($capacidades['convertir'], 'manage-quotes tambien debe poder convertir');
    }

    public function test_lector_no_convierte(): void
    {
        $capacidades = QuoteAbilities::para($this->usuarioCon(['quotes.ver']), $this->proyecto());

        $this->assertTrue($capacidades['ver']);
        $this->assertFalse($capacidades['editar']);
        $this->assertFalse($capacidades['convertir']);
    }

    public function test_sin_modulo_de_pedidos_no_se_puede_convertir(): void
    {
        // Aunque tenga el permiso: el pedido no tendria donde vivir.
        $user = $this->usuarioCon(['quotes.editar']);
        $capacidades = QuoteAbilities::para($user, $this->proyecto(conModuloPedidos: false));

        $this->assertTrue($capacidades['editar'], 'el permiso sigue estando');
        $this->assertFalse($capacidades['convertir'], 'pero sin modulo orders no se convierte');
    }

    public function test_sin_proyecto_no_se_puede_convertir(): void
    {
        $capacidades = QuoteAbilities::para($this->usuarioCon(['quotes.editar']), null);

        $this->assertFalse($capacidades['convertir'], 'sin proyecto no hay modulo que comprobar');
    }

    public function test_superadmin_puede_todo(): void
    {
        $user = User::factory()->create(['is_superadmin' => 1]);
        $capacidades = QuoteAbilities::para($user->fresh(), $this->proyecto());

        foreach (['ver', 'crear', 'editar', 'eliminar', 'convertir'] as $accion) {
            $this->assertTrue($capacidades[$accion], "superadmin deberia poder '{$accion}'");
        }
    }
}
