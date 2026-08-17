<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASE 3 — resto de módulos: bots, listas maestras, certificados, combos,
 * promociones, cupones, sedes, grupos y propuestas.
 *
 * Todas estas acciones estaban abiertas a cualquier miembro del proyecto. Se
 * cierran con permisos que YA existían y que los roles reales ya tienen, para no
 * dejar a nadie fuera:
 *
 *   bots · sedes · grupos · meta comercial  -> settings.negocio   (admin, gerente)
 *   listas maestras                          -> settings.catalogos (admin, gerente)
 *   certificados digitales                   -> invoices.editar    (admin, contador, gerente)
 *   combos · promociones · cupones           -> catalog.editar / catalog.eliminar
 *   propuestas                               -> quotes.crear / .editar / .eliminar
 */
class ModulesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISOS = [
        'settings.negocio', 'settings.catalogos', 'invoices.editar',
        'catalog.editar', 'catalog.eliminar',
        'quotes.crear', 'quotes.editar', 'quotes.eliminar',
    ];

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::PERMISOS as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio modulos',
            'slug'      => 'negocio-modulos',
            'is_active' => true,
        ]);
    }

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

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);

        return $user;
    }

    /** @dataProvider accionesCerradas */
    public function test_un_miembro_sin_permiso_no_puede(string $verbo, string $ruta, string $permiso): void
    {
        $this->usuarioConRol('miembro_pelado_test', []);

        $this->json($verbo, $ruta, [])->assertForbidden();
    }

    /** Quien tiene el permiso correspondiente no queda bloqueado. */
    /** @dataProvider accionesCerradas */
    public function test_quien_tiene_el_permiso_no_recibe_403(string $verbo, string $ruta, string $permiso): void
    {
        $this->usuarioConRol('con_'.md5($permiso), [$permiso]);

        $respuesta = $this->json($verbo, $ruta, []);

        $this->assertNotSame(
            403,
            $respuesta->status(),
            "{$verbo} {$ruta} devolvió 403 a quien tiene {$permiso}."
        );
    }

    public static function accionesCerradas(): array
    {
        return [
            'crear bot'          => ['POST', '/bixoadmin/bots/instances',       'settings.negocio'],
            'controlar bot'      => ['POST', '/bixoadmin/bots/control',         'settings.negocio'],
            'reiniciar sesión'   => ['POST', '/bixoadmin/bots/reset-session',   'settings.negocio'],
            'crear estado bot'   => ['POST', '/bixoadmin/bots/states',          'settings.negocio'],
            'crear sede'         => ['POST', '/bixoadmin/company/sedes',        'settings.negocio'],
            'crear grupo'        => ['POST', '/bixoadmin/company/groups',       'settings.negocio'],
            'meta comercial'     => ['POST', '/bixoadmin/dashboard-comercial/meta', 'settings.negocio'],
            'lista maestra'      => ['POST', '/bixoadmin/catalogs',             'settings.catalogos'],
            'certificado'        => ['POST', '/bixoadmin/certificados',         'invoices.editar'],
            'crear combo'        => ['POST', '/bixoadmin/combos',               'catalog.editar'],
            'crear promoción'    => ['POST', '/bixoadmin/promotions',           'catalog.editar'],
            'crear cupón'        => ['POST', '/bixoadmin/coupons',              'catalog.editar'],
            'crear propuesta'    => ['POST', '/bixoadmin/proposals',            'quotes.crear'],
        ];
    }

    /**
     * Los endpoints de autenticación siguen abiertos a propósito: exigirles
     * permiso impediría iniciar sesión o recuperar la contraseña.
     */
    public function test_la_recuperacion_de_contrasena_sigue_siendo_publica(): void
    {
        $respuesta = $this->postJson('/bixosales/get-projects', ['email' => 'nadie@example.test']);

        // 422 (email desconocido) es la respuesta correcta y demuestra que el
        // endpoint sigue siendo alcanzable sin permisos. Lo que no puede dar es 403.
        $this->assertNotSame(403, $respuesta->status());
    }
}
