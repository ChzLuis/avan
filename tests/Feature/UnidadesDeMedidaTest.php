<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\CatalogList;
use App\Modules\Catalogo\Models\CatalogValue;
use App\Models\Employee;
use App\Models\Module;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Modules\Catalogo\Support\UnidadesMedida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Las unidades con las que se vende salen de una tabla, no de la memoria.
 *
 * `products.unit` era texto libre: el mismo kilo se guardaba como "kg", "Kg.",
 * "kilo" o "KILOGRAMO" según quién diera de alta el producto, y el campo no
 * servía para agrupar, comparar ni declarar nada. Ahora el editor ofrece la
 * tabla de medidas de SUNAT, sin dejar de admitir lo que el negocio escriba.
 */
class UnidadesDeMedidaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['catalog.ver', 'catalog.crear', 'catalog.editar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería QA', 'slug' => 'ferre-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('unidades_qa', 'web')
            ->syncPermissions(['catalog.ver', 'catalog.crear', 'catalog.editar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);

        $this->actingAs($u)->withSession(['active_project_id' => $this->project->id]);
    }

    /** La tabla está completa y sin repetidos. */
    public function test_la_tabla_de_medidas_no_tiene_repetidos(): void
    {
        $todas = UnidadesMedida::todas();

        $this->assertSame(array_values(array_unique($todas)), $todas, 'hay unidades duplicadas');
        $this->assertGreaterThan(55, count($todas), 'faltan unidades de la tabla');

        foreach (['UNIDAD', 'KILOGRAMO', 'CAJA', 'METRO CUADRADO', 'MILLARES', 'TONELADA LARGA'] as $u) {
            $this->assertContains($u, $todas, $u.' no está en la tabla');
        }
    }

    /** Y llega al editor de productos, que es donde hace falta. */
    public function test_el_editor_ofrece_la_tabla_de_medidas(): void
    {
        $html = $this->get('/bixoadmin/products')->assertSuccessful()->getContent();

        $this->assertStringContainsString('list="pe-unidades"', $html, 'el campo no ofrece sugerencias');

        foreach (['KILOGRAMO', 'METRO CUBICO', 'MILLON DE UNIDADES', 'PIES CUADRADOS'] as $u) {
            $this->assertStringContainsString('value="'.$u.'"', $html, $u.' no llega a la pantalla');
        }
    }

    /**
     * Sigue siendo un campo de texto: obligar a elegir de la lista dejaría sin
     * poder guardar a quien vende por "bidón" o por "juego de 3".
     */
    public function test_se_puede_escribir_una_unidad_que_no_esta_en_la_tabla(): void
    {
        $this->postJson('/bixoadmin/products', [
            'name' => 'Cemento a granel', 'price' => '25.00', 'unit' => 'Bidón de obra',
        ])->assertSuccessful();

        $this->assertSame('Bidón de obra', Product::latest('id')->first()->unit);
    }

    /** Una unidad de la tabla se guarda tal cual, sin traducciones por el camino. */
    public function test_una_unidad_de_la_tabla_se_guarda_como_es(): void
    {
        $this->postJson('/bixoadmin/products', [
            'name' => 'Fierro corrugado', 'price' => '18.50', 'unit' => 'KILOGRAMO',
        ])->assertSuccessful();

        $this->assertSame('KILOGRAMO', Product::latest('id')->first()->unit);
    }

    /** Lo que el negocio ya tenía en su catálogo no se pierde: va primero. */
    public function test_las_unidades_propias_del_negocio_van_delante(): void
    {
        $grupos = UnidadesMedida::paraNegocio('retail', ['Saco de 42.5 kg', 'Rollo']);

        $this->assertSame(['Saco de 42.5 kg', 'Rollo'], $grupos['Tus unidades']);
        $this->assertSame('Tus unidades', array_key_first($grupos));
    }

    /** Y no se repiten si coinciden con una de la tabla, aunque cambie la caja. */
    public function test_una_unidad_propia_no_se_repite_con_la_tabla(): void
    {
        $grupos = UnidadesMedida::paraNegocio(null, ['kilogramo']);

        $planas = array_merge(...array_values($grupos));
        $mayus = array_map('mb_strtoupper', $planas);

        $this->assertSame(count($mayus), count(array_unique($mayus)), 'la unidad propia se repite con la de la tabla');
        $this->assertContains('kilogramo', $planas, 'debe quedar la del negocio, tal como la escribió');
    }

    /** Un restaurante ve antes sus presentaciones, pero la tabla sigue debajo. */
    public function test_el_rubro_pone_sus_presentaciones_antes_de_las_medidas(): void
    {
        $grupos = UnidadesMedida::paraNegocio('restaurante');

        $this->assertContains('1/4 pollo', $grupos['Presentaciones de tu rubro']);
        $this->assertContains('KILOGRAMO', array_merge($grupos['Más usadas'], $grupos['Unidades de medida']));
        $this->assertLessThan(
            array_search('Unidades de medida', array_keys($grupos), true),
            array_search('Presentaciones de tu rubro', array_keys($grupos), true),
            'las presentaciones del rubro tienen que ir antes'
        );
    }

    /** El catálogo propio del negocio es el que alimenta "Tus unidades". */
    public function test_el_catalogo_del_negocio_llega_a_la_pantalla(): void
    {
        $lista = CatalogList::create([
            'project_id' => $this->project->id, 'type' => 'unit', 'name' => 'Unidades', 'is_active' => true,
        ]);
        CatalogValue::create([
            'catalog_list_id' => $lista->id, 'label' => 'Varilla de 9 m', 'is_active' => true, 'sort_order' => 1,
        ]);

        $html = $this->get('/bixoadmin/products')->assertSuccessful()->getContent();

        $this->assertStringContainsString('value="Varilla de 9 m"', $html);
    }
}
