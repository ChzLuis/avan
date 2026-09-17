<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Support\HeaderPresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El botón "Categorías" del menú y cómo se despliegan las categorías.
 *
 * Lo que se defiende:
 *  1. El botón es UNO para todos los layouts (shell de presets, clásico y
 *     banda), y lo gobierna el Constructor: posición, estilo, texto y modo.
 *  2. En la banda va a la IZQUIERDA integrado con el menú, no como texto
 *     suelto al final (lo que la hacía ver muerta).
 *  3. El modo "lista" existe y pinta subcategorías.
 *  4. "Multiuniverso" ya no se ofrece, pero quien lo tuviera no se rompe.
 *  5. Ningún ajuste tiene dos editores en la etapa (el auditor lo caza).
 */
class MenuCategoriasTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $settings): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Menú', 'slug' => 'menu-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['catalog_template' => 'computienda'] + $settings as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }
        $padre = Category::create(['project_id' => $project->id, 'name' => 'CABLES RAIZ', 'slug' => 'cab-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $hija  = Category::create(['project_id' => $project->id, 'name' => 'THW-90 HIJA', 'slug' => 'thw-'.uniqid(), 'is_active' => true, 'parent_id' => $padre->id]);
        $luces = Category::create(['project_id' => $project->id, 'name' => 'LUCES RAIZ', 'slug' => 'luz-'.uniqid(), 'is_active' => true, 'sort_order' => 2]);
        // La tienda oculta del menú las categorías (y subcategorías) sin
        // productos: cada rama necesita al menos uno para salir.
        foreach ([$hija, $luces] as $cat) {
            Product::create([
                'project_id' => $project->id, 'category_id' => $cat->id,
                'name' => 'Producto '.$cat->name, 'slug' => 'p-'.uniqid(),
                'price' => 10, 'stock' => 5, 'is_available' => true,
            ]);
        }

        return $project;
    }

    private function html(Project $p): string
    {
        return $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();
    }

    /** Multiuniverso fuera del selector, pero resoluble para quien lo tenga. */
    public function test_multiuniverso_se_retira_sin_romper(): void
    {
        $claves = array_column(HeaderPresets::forBuilder(), 'key');

        $this->assertNotContains('multiverse', $claves, 'Multiuniverso no debe ofrecerse.');
        $this->assertContains('mega_menu', $claves);
        $this->assertNotNull(HeaderPresets::get('multiverse'), 'Una tienda que lo tuviera guardado debe seguir renderizando.');
    }

    /** Shell de presets: estilo y texto del botón salen del Constructor. */
    public function test_shell_boton_con_estilo_y_texto(): void
    {
        $html = $this->html($this->tienda([
            'header_preset' => 'mega_menu', 'hp_cats_style' => 'contorno', 'mega_button_text' => 'Rubros',
        ]));

        $this->assertStringContainsString('cats-btn--contorno', $html);
        $this->assertStringContainsString('<span>Rubros</span>', $html);
    }

    /** Oculto = ni botón ni desplegable. */
    public function test_boton_oculto(): void
    {
        $html = $this->html($this->tienda(['header_preset' => 'mega_menu', 'hp_cats_pos' => 'oculto']));

        $this->assertStringNotContainsString('cats-btn', $html);
    }

    /** Modo lista: desplegable simple con las subcategorías. */
    public function test_modo_lista_pinta_subcategorias(): void
    {
        $html = $this->html($this->tienda(['header_preset' => 'mega_menu', 'hp_cat_trigger' => 'lista']));

        $this->assertStringContainsString('cats-list', $html);
        $this->assertStringContainsString('THW-90 HIJA', $html, 'La subcategoría debe salir en el desplegable.');
        $this->assertStringContainsString('Ver todo CABLES RAIZ', $html);
    }

    /** Banda: el botón va a la izquierda, ANTES del primer enlace del menú. */
    public function test_banda_boton_a_la_izquierda(): void
    {
        $html = $this->html($this->tienda(['header_layout' => 'banda']));

        $boton = strpos($html, 'cats-btn');
        $enlace = strpos($html, 'class="hba-link');

        $this->assertNotFalse($boton, 'La banda debe llevar el botón común.');
        $this->assertNotFalse($enlace);
        $this->assertLessThan($enlace, $boton, 'El botón debe ir antes de los enlaces, no al final.');
        $this->assertStringContainsString('has-cats-left', $html, 'La barra debe alinearse a la izquierda con el botón.');
        $this->assertStringNotContainsString('hba-cats-panel', $html, 'La lista vieja de 10 categorías ya no se usa.');
    }

    /** Banda a la derecha: el botón va DESPUÉS de los enlaces. */
    public function test_banda_boton_a_la_derecha(): void
    {
        $html = $this->html($this->tienda(['header_layout' => 'banda', 'hp_cats_pos' => 'derecha']));

        $this->assertGreaterThan(strpos($html, 'class="hba-link'), strpos($html, 'cats-btn'));
        $this->assertStringContainsString('cats-menu--derecha', $html);
    }

    /** Clásico: también usa el componente y respeta el modo lista. */
    public function test_clasico_usa_el_componente(): void
    {
        $html = $this->html($this->tienda(['hp_cat_trigger' => 'lista', 'hp_cats_style' => 'texto']));

        $this->assertStringContainsString('cats-btn--texto', $html);
        $this->assertStringContainsString('cats-list', $html);
        $this->assertStringNotContainsString('Todas las categorías</button>', $html, 'El botón fijo antiguo del clásico ya no debe salir.');
    }

    /** Un solo editor por ajuste en la etapa; el auditor no debe ver duplicados. */
    public function test_sin_editores_duplicados(): void
    {
        $etapa = file_get_contents(resource_path('views/settings/builder/stages/header.blade.php'));

        foreach (['hp_cat_trigger', 'mega_button_text', 'hp_cats_pos', 'hp_cats_style'] as $clave) {
            $this->assertSame(1, substr_count($etapa, "setSetting('{$clave}'"), "La clave {$clave} debe tener exactamente un editor.");
        }

        $auditoria = \App\Storefront\ConstructorAuditor::make()->auditar();
        $this->assertSame(0, is_array($auditoria['DUPLICATE_EDITORS'] ?? null) ? count($auditoria['DUPLICATE_EDITORS']) : (int) ($auditoria['DUPLICATE_EDITORS'] ?? 0));
    }
}
