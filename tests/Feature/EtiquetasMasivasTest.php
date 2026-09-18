<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Category;
use App\Models\Module;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Modules\Catalogo\Support\EtiquetasProducto as Etq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etiquetar productos en masa desde el listado del catalogo.
 *
 * Lo que se defiende: que etiquetar veinte productos de golpe deje cada uno
 * EXACTAMENTE como si se hubiera etiquetado a mano — mismo saneo, mismas
 * claves, sin pisar las tallas ni la etiqueta personalizada que ya tuvieran —
 * y que jamas toque productos de otro negocio.
 */
class EtiquetasMasivasTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Masivo', 'slug' => 'msv-'.uniqid(), 'is_active' => true,
        ]);
        // La ruta pasa por `module:catalog`: sin el modulo, redirect y nada.
        $modulo = Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo']);
        $this->project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
    }

    private function producto(string $nombre, ?array $options = null, ?Project $p = null): Product
    {
        $p ??= $this->project;
        $cat = Category::firstOrCreate(
            ['project_id' => $p->id, 'slug' => 'c-'.$p->id],
            ['name' => 'LED', 'is_active' => true]
        );

        return Product::create([
            'project_id' => $p->id, 'category_id' => $cat->id, 'name' => $nombre,
            'slug' => 'p-'.uniqid(), 'price' => 10, 'stock' => 1, 'is_available' => true,
            'options' => $options,
        ]);
    }

    private function masivo(array $ids, array $extra): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)
            ->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('products.bulk-action'), ['ids' => $ids, 'action' => 'etiquetas'] + $extra);
    }

    /** Añadir suma a lo que habia y respeta tallas y personalizada. */
    public function test_agregar_suma_y_conserva_lo_demas(): void
    {
        $a = $this->producto('A', [
            'sizes' => ['S', 'M'],
            'etiquetas' => Etq::normaliza(['claves' => ['nuevo'],
                'personalizada' => ['texto' => 'Pago en cuotas', 'fondo' => '#111111', 'texto_color' => '#FFFFFF']]),
        ]);
        $b = $this->producto('B');

        $this->masivo([$a->id, $b->id], ['etq_mode' => 'agregar', 'etq_claves' => ['oferta', 'solar']])
            ->assertOk()->assertJson(['ok' => true, 'count' => 2]);

        $ea = Etq::config($a->fresh());
        $this->assertEqualsCanonicalizing(['nuevo', 'oferta', 'solar'], $ea['claves']);
        $this->assertSame('Pago en cuotas', $ea['personalizada']['texto'], 'La personalizada es del producto: no se pierde.');
        $this->assertSame(['S', 'M'], $a->fresh()->options['sizes'], 'Las tallas comparten options y no deben pisarse.');

        $this->assertEqualsCanonicalizing(['oferta', 'solar'], Etq::config($b->fresh())['claves']);
    }

    /** Quitar resta solo lo indicado. */
    public function test_quitar_resta(): void
    {
        $a = $this->producto('A', ['etiquetas' => Etq::normaliza(['claves' => ['nuevo', 'oferta', 'solar']])]);

        $this->masivo([$a->id], ['etq_mode' => 'quitar', 'etq_claves' => ['oferta']])->assertOk();

        $this->assertEqualsCanonicalizing(['nuevo', 'solar'], Etq::config($a->fresh())['claves']);
    }

    /** Reemplazar deja exactamente lo marcado; vacio = sin etiquetas y sin basura en options. */
    public function test_reemplazar(): void
    {
        $a = $this->producto('A', ['sizes' => ['L'], 'etiquetas' => Etq::normaliza(['claves' => ['nuevo', 'oferta']])]);

        $this->masivo([$a->id], ['etq_mode' => 'reemplazar', 'etq_claves' => ['ip65']])->assertOk();
        $this->assertSame(['ip65'], Etq::config($a->fresh())['claves']);

        $this->masivo([$a->id], ['etq_mode' => 'reemplazar', 'etq_claves' => []])->assertOk();
        $o = $a->fresh()->options;
        $this->assertArrayNotHasKey('etiquetas', $o, 'Sin etiquetas no debe quedar la clave vacia.');
        $this->assertSame(['L'], $o['sizes'], 'Las tallas sobreviven al vaciado.');
    }

    /** Claves inventadas se descartan; no revientan ni se cuelan. */
    public function test_ignora_claves_inventadas(): void
    {
        $a = $this->producto('A');

        $this->masivo([$a->id], ['etq_mode' => 'agregar', 'etq_claves' => ['nuevo', 'inventada', '<script>']])->assertOk();

        $this->assertSame(['nuevo'], Etq::config($a->fresh())['claves']);
    }

    /** Ids de otro negocio se ignoran aunque vengan en la lista. */
    public function test_no_toca_productos_de_otro_negocio(): void
    {
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-'.uniqid(), 'is_active' => true]);
        $ajeno = $this->producto('Ajeno', null, $otro);
        $mio = $this->producto('Mio');

        $this->masivo([$mio->id, $ajeno->id], ['etq_mode' => 'agregar', 'etq_claves' => ['oferta']])
            ->assertOk()->assertJson(['count' => 1]);

        $this->assertNull($ajeno->fresh()->options, 'El producto del otro negocio no se ha tocado.');
        $this->assertSame(['oferta'], Etq::config($mio->fresh())['claves']);
    }

    /** Lo etiquetado en masa llega a la tienda igual que lo etiquetado a mano. */
    public function test_llega_a_la_tienda(): void
    {
        $a = $this->producto('Reflector masivo');
        $this->project->settings()->create(['key' => 'catalog_template', 'value' => 'computienda']);

        $this->masivo([$a->id], ['etq_mode' => 'agregar', 'etq_claves' => ['stock_limitado']])->assertOk();

        $html = $this->get(route('public.shop', $this->project->slug))->assertOk()->getContent();
        $this->assertStringContainsString('Stock limitado', $html);
    }

    /** El listado ofrece la accion. */
    public function test_el_listado_ofrece_la_accion(): void
    {
        $vista = file_get_contents(app_path('Modules/Catalogo/Views/catalog/products/index.blade.php'));

        $this->assertStringContainsString("view==='etiquetas'", $vista, 'Falta el panel de etiquetas en masa.');
        $this->assertStringContainsString("runBulk('etiquetas'", $vista, 'El panel no dispara la accion.');
    }
}
