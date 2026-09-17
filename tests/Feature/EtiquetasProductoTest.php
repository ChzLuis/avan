<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Support\EtiquetasProducto as Etq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etiquetas de producto.
 *
 * Lo que estas pruebas defienden, por orden de importancia:
 *
 *  1. Que la tarjeta NO se sature: pase lo que pase, como maximo dos.
 *  2. Que gane la que mas vende: la prioridad manda, no el orden de marcado.
 *  3. Que lo que se guarda llegue a la tienda — el fallo de siempre en este
 *     proyecto es el ajuste que existe, se guarda y nunca se pinta.
 *  4. Que no se pueda inyectar nada por el color ni por la clave.
 */
class EtiquetasProductoTest extends TestCase
{
    use RefreshDatabase;

    private function producto(array $etiquetas = [], array $extra = []): Product
    {
        $project = Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Tienda Etiquetas', 'slug' => 'etq-'.uniqid(), 'is_active' => true,
        ]);
        // Las rutas de producto pasan por `module:catalog`: sin el modulo
        // activo la peticion se va en redirect y no llega al controlador.
        $modulo = \App\Models\Module::firstOrCreate(['key' => 'catalog'], ['name' => 'Catálogo']);
        $project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);

        $cat = Category::create(['project_id' => $project->id, 'name' => 'LED', 'slug' => 'led-'.uniqid(), 'is_active' => true]);

        return Product::create([
            'project_id' => $project->id, 'category_id' => $cat->id,
            'name' => 'Reflector 100W', 'slug' => 'p-'.uniqid(),
            'price' => 100, 'stock' => 5, 'is_available' => true,
            'options' => $etiquetas ? ['etiquetas' => Etq::normaliza($etiquetas)] : null,
        ] + $extra);
    }

    /** Sin etiquetas no se pinta nada: ni un hueco reservado. */
    public function test_producto_sin_etiquetas(): void
    {
        $this->assertSame([], $this->producto()->etiquetas('card'));
        $this->assertSame([], $this->producto()->etiquetas('ficha'));
    }

    /** Una etiqueta sale con su texto y los colores de su familia. */
    public function test_producto_con_una_etiqueta(): void
    {
        $e = $this->producto(['claves' => ['nuevo']])->etiquetas('card');

        $this->assertCount(1, $e);
        $this->assertSame('Nuevo', $e[0]['texto']);
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $e[0]['fondo']);
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $e[0]['texto_color']);
    }

    /** Dos caben enteras. */
    public function test_producto_con_dos_etiquetas(): void
    {
        $this->assertCount(2, $this->producto(['claves' => ['oferta', 'producto_original']])->etiquetas('card'));
    }

    /**
     * Con cuatro, la tarjeta muestra SOLO las dos de mayor prioridad.
     *
     * Es la regla que evita el catalogo recargado. Se marcan a proposito en
     * orden "malo" (la tecnica primero) para comprobar que manda la prioridad
     * y no el orden en que el negocio las eligio.
     */
    public function test_con_cuatro_etiquetas_la_tarjeta_muestra_las_dos_prioritarias(): void
    {
        $p = $this->producto(['claves' => ['ip65', 'solar', 'oferta', 'producto_original']]);

        $card = array_column($p->etiquetas('card'), 'texto');

        $this->assertCount(Etq::MAX_EN_CARD, $card);
        $this->assertSame(['Oferta', 'Producto original'], $card);
    }

    /** En la ficha hay sitio: se ven todas. */
    public function test_la_ficha_muestra_todas(): void
    {
        $p = $this->producto(['claves' => ['ip65', 'solar', 'oferta', 'producto_original']]);

        $this->assertCount(4, $p->etiquetas('ficha'));
    }

    /** Los interruptores apagan cada superficie por separado. */
    public function test_los_interruptores_por_superficie(): void
    {
        $soloFicha = $this->producto(['claves' => ['nuevo'], 'card' => false, 'ficha' => true]);
        $this->assertSame([], $soloFicha->etiquetas('card'));
        $this->assertCount(1, $soloFicha->etiquetas('ficha'));

        $soloCard = $this->producto(['claves' => ['nuevo'], 'card' => true, 'ficha' => false]);
        $this->assertCount(1, $soloCard->etiquetas('card'));
        $this->assertSame([], $soloCard->etiquetas('ficha'));
    }

    /** La etiqueta propia compite por prioridad como una mas. */
    public function test_etiqueta_personalizada(): void
    {
        $p = $this->producto([
            'claves' => ['oferta', 'nuevo'],
            'personalizada' => ['texto' => 'Pago en cuotas', 'fondo' => '#0F6FCB',
                'texto_color' => '#FFFFFF', 'posicion' => 'derecha', 'prioridad' => 1],
        ]);

        $card = $p->etiquetas('card');
        $this->assertSame('Pago en cuotas', $card[0]['texto'], 'Con prioridad 1 debe ir primera.');
        $this->assertSame('derecha', $card[0]['posicion']);
    }

    /** Sin texto no hay etiqueta personalizada: no se guarda basura. */
    public function test_personalizada_sin_texto_se_descarta(): void
    {
        $p = $this->producto(['claves' => ['nuevo'], 'personalizada' => ['texto' => '   ', 'fondo' => '#000000']]);

        $this->assertCount(1, $p->etiquetas('card'));
        $this->assertNull(Etq::config($p)['personalizada']);
    }

    /**
     * Ni claves inventadas ni colores que no sean hexadecimales.
     *
     * Los colores acaban dentro de un atributo `style`, asi que un valor libre
     * seria una via de inyeccion.
     */
    public function test_no_se_cuela_basura(): void
    {
        $this->assertNull(Etq::normaliza(['claves' => ['inventada', '<script>', '']]));

        $limpio = Etq::normaliza([
            'claves' => ['nuevo', 'no_existe'],
            'personalizada' => ['texto' => 'X', 'fondo' => 'javascript:alert(1)', 'texto_color' => 'red'],
        ]);

        $this->assertSame(['nuevo'], $limpio['claves']);
        $this->assertSame('#1F2937', $limpio['personalizada']['fondo'], 'Un color no hexadecimal cae al de por defecto.');
        $this->assertSame('#FFFFFF', $limpio['personalizada']['texto_color']);
    }

    /** Las 42 del catalogo tienen texto, familia y colores validos. */
    public function test_el_catalogo_esta_completo_y_es_valido(): void
    {
        $catalogo = Etq::catalogo();
        $this->assertGreaterThanOrEqual(42, count($catalogo));

        foreach ($catalogo as $clave => $e) {
            $this->assertNotSame('', trim($e['texto']), "La etiqueta {$clave} no tiene texto.");
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $e['fondo'], "Fondo invalido en {$clave}.");
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $e['texto_color'], "Color de texto invalido en {$clave}.");
        }

        // Las prioridades no se repiten: si empataran, el orden seria azaroso.
        $prioridades = array_column($catalogo, 'prioridad');
        $this->assertSame(count($prioridades), count(array_unique($prioridades)));
    }

    /**
     * El camino completo: se guardan por el panel y llegan a la tienda.
     *
     * Cierra la puerta al fallo recurrente del proyecto — el ajuste que se
     * configura, se guarda y nunca se pinta.
     */
    public function test_lo_guardado_llega_a_la_tienda(): void
    {
        $p = $this->producto(['claves' => ['oferta', 'stock_limitado']]);
        $project = $p->project;
        $project->settings()->create(['key' => 'catalog_template', 'value' => 'computienda']);

        $html = $this->get(route('public.shop', $project->slug))->assertOk()->getContent();

        $this->assertStringContainsString('Oferta', $html, 'La etiqueta no llega al catálogo.');
        $this->assertStringContainsString('pe-etq', $html, 'Falta el marcado de la etiqueta.');
    }

    /** Guardar por el panel deja `options.etiquetas` bien formado. */
    public function test_el_panel_guarda_las_etiquetas(): void
    {
        $p = $this->producto();
        $user = $p->project->owner;

        $this->actingAs($user)->withSession(['active_project_id' => $p->project_id])
            ->put(route('products.update', $p), [
                'name' => $p->name, 'price' => 100,
                'etiquetas' => json_encode(['card' => true, 'ficha' => true, 'claves' => ['nuevo', 'solar']]),
            ]);

        $guardado = $p->fresh()->options['etiquetas'] ?? null;
        $this->assertSame(['nuevo', 'solar'], $guardado['claves'] ?? null);
    }

    /** Guardar etiquetas NO borra las tallas: comparten `options`. */
    public function test_no_se_pisan_con_las_tallas(): void
    {
        $p = $this->producto();
        $p->options = ['sizes' => ['S', 'M']];
        $p->save();

        $user = $p->project->owner;
        $this->actingAs($user)->withSession(['active_project_id' => $p->project_id])
            ->put(route('products.update', $p), [
                'name' => $p->name, 'price' => 100, 'sizes' => 'S, M',
                'etiquetas' => json_encode(['claves' => ['nuevo']]),
            ]);

        $o = $p->fresh()->options;
        $this->assertSame(['S', 'M'], $o['sizes'] ?? null, 'Las tallas se perdieron al guardar etiquetas.');
        $this->assertSame(['nuevo'], $o['etiquetas']['claves'] ?? null);
    }
}
