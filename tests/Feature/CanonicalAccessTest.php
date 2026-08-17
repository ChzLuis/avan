<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASE 4 — Modelo canónico: 12 áreas × 3 niveles.
 *
 * Lo que se protege aquí es la regla de herencia (§6 del plan):
 *
 *     Administrar incluye Trabajar · Trabajar incluye Ver
 *
 * No se guarda por triplicado en la base: un perfil tiene UNA fila por área y la
 * herencia se resuelve al comprobar el permiso. Por eso es imposible que exista
 * «editar sin ver», y por eso hay que probarlo: si el Gate dejara de resolverlo,
 * medio sistema se cerraría en silencio.
 */
class CanonicalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Access::permisos() as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    private function usuarioCon(array $permisos): User
    {
        Role::findOrCreate('perfil_test_'.md5(implode(',', $permisos)), 'web')->syncPermissions($permisos);

        $u = User::factory()->create(['is_superadmin' => 0]);
        $u->syncRoles(['perfil_test_'.md5(implode(',', $permisos))]);

        return $u->fresh();
    }

    public function test_hay_exactamente_36_permisos_y_12_areas(): void
    {
        $this->assertCount(12, Access::AREAS);
        $this->assertCount(36, Access::permisos());
        $this->assertCount(36, array_unique(Access::permisos()), 'Hay nombres de permiso repetidos.');
    }

    public function test_administrar_incluye_trabajar_y_ver(): void
    {
        $u = $this->usuarioCon(['catalogo.administrar']);

        $this->assertTrue($u->can('catalogo.administrar'));
        $this->assertTrue($u->can('catalogo.trabajar'), 'Administrar no concedió Trabajar.');
        $this->assertTrue($u->can('catalogo.ver'), 'Administrar no concedió Ver.');
    }

    public function test_trabajar_incluye_ver_pero_no_administrar(): void
    {
        $u = $this->usuarioCon(['catalogo.trabajar']);

        $this->assertTrue($u->can('catalogo.ver'));
        $this->assertTrue($u->can('catalogo.trabajar'));
        $this->assertFalse($u->can('catalogo.administrar'), 'Trabajar concedió Administrar: la jerarquía va al revés.');
    }

    public function test_ver_no_concede_nada_mas(): void
    {
        $u = $this->usuarioCon(['catalogo.ver']);

        $this->assertTrue($u->can('catalogo.ver'));
        $this->assertFalse($u->can('catalogo.trabajar'));
        $this->assertFalse($u->can('catalogo.administrar'));
    }

    /** La herencia no puede desbordarse de un área a otra. */
    public function test_la_herencia_no_cruza_de_area(): void
    {
        $u = $this->usuarioCon(['catalogo.administrar']);

        foreach (['inventario', 'pedidos', 'caja', 'configuracion'] as $otra) {
            $this->assertFalse($u->can("{$otra}.ver"), "Administrar en Catálogo concedió Ver en {$otra}.");
        }
    }

    /** Sin acceso es la ausencia del permiso: no hace falta un cuarto nivel. */
    public function test_sin_acceso_es_no_tener_ninguno_de_los_tres(): void
    {
        $u = $this->usuarioCon([]);

        foreach (Access::permisos() as $p) {
            $this->assertFalse($u->can($p), "Un perfil sin permisos pudo {$p}.");
        }
    }

    /** La herencia se aplica en las 12 áreas, no solo en la que se probó. */
    public function test_la_herencia_funciona_en_las_doce_areas(): void
    {
        foreach (array_keys(Access::AREAS) as $area) {
            $u = $this->usuarioCon(["{$area}.administrar"]);

            $this->assertTrue($u->can("{$area}.ver"), "Falla la herencia en {$area}.");
            $this->assertTrue($u->can("{$area}.trabajar"), "Falla la herencia en {$area}.");
        }
    }

    /** El Gate de herencia solo concede: nunca puede denegar un permiso legacy. */
    public function test_no_interfiere_con_los_permisos_antiguos(): void
    {
        Permission::findOrCreate('catalog.ver', 'web');
        $u = $this->usuarioCon(['catalog.ver']);

        $this->assertTrue($u->can('catalog.ver'), 'El Gate de herencia rompió un permiso legacy.');
        $this->assertFalse($u->can('catalogo.ver'), 'Un permiso legacy concedió su equivalente canónico.');
    }

    /** El dueño del negocio sigue teniendo todo, sin permisos explícitos. */
    public function test_el_dueno_conserva_todo(): void
    {
        $dueno = User::factory()->create(['is_superadmin' => 0]);
        $project = Project::create([
            'owner_id' => $dueno->id, 'name' => 'Suyo', 'slug' => 'suyo-test', 'is_active' => true,
        ]);
        app()->instance('active_project', $project);

        foreach (Access::permisos() as $p) {
            $this->assertTrue($dueno->can($p), "El dueño no pudo {$p}.");
        }
    }

    /** Cada permiso tiene etiqueta y descripción: la ayuda contextual sale de aquí. */
    public function test_todos_los_permisos_tienen_etiqueta_y_descripcion(): void
    {
        foreach (Access::permisos() as $p) {
            $this->assertNotNull(Access::etiqueta($p), "Sin etiqueta: {$p}");
            $this->assertNotEmpty(Access::descripcion($p), "Sin descripción: {$p}");
        }
    }

    public function test_un_permiso_que_no_es_canonico_no_tiene_jerarquia(): void
    {
        $this->assertSame([], Access::equivalentesSuperiores('orders.ver'));
        $this->assertSame([], Access::equivalentesSuperiores('inventado'));
        $this->assertNull(Access::partes('catalogo.inventado'));
    }
}
