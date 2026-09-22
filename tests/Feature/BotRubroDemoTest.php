<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Ver una tienda" debe mostrar la demo DEL RUBRO del cliente.
 *
 * Casos reales (19 y 20 de septiembre): un cliente que vende galletas, agua y
 * gaseosas recibio la demo de una ferreteria; otro pidio veterinaria y el
 * asesor tuvo que mandarla a mano hora y media despues. Existen 7 demos y el
 * bot solo ofrecia una.
 *
 * El selector es un bloque 'intencion', NO 'lista': el motor no sabe resolver
 * la respuesta a un bloque 'lista' (devuelve null, el flujo muere y el cliente
 * queda sin respuesta). Se comprobo en produccion antes de corregirlo.
 */
class BotRubroDemoTest extends TestCase
{
    use RefreshDatabase;

    private Project $p;
    private array $def;

    protected function setUp(): void
    {
        parent::setUp();
        $this->p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Eskala', 'slug' => 'eskala-test', 'is_active' => true,
        ]);
        $this->def = json_decode(
            file_get_contents(base_path('tests/Fixtures/flujo_eskala_rubros.json')),
            true
        );
    }

    /** Recorre el flujo y devuelve el texto de todas las respuestas. */
    private function conversar(array $mensajes): string
    {
        $runner = new FlowRunner($this->p, $this->def);
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];
        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, '51900000777');
            $estado = $res['estado'];
            foreach ($res['respuestas'] as $r) {
                // Un globo puede ser texto o un arreglo (imagen, lista, enlace):
                // se serializa entero para que el enlace de la demo se vea igual
                // venga en 'url', en 'enlace' o en el cuerpo.
                $salida[] = is_array($r) ? json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    public function test_el_selector_de_rubro_no_es_un_bloque_lista(): void
    {
        $this->assertSame('intencion', $this->def['bloques']['rubro']['tipo'],
            'Un bloque "lista" deja el flujo muerto: el motor no resuelve su respuesta.');
    }

    public static function rubros(): array
    {
        return [
            'minimarket'  => ['vendo abarrotes y gaseosas', 'demomarket'],
            'ferreteria'  => ['tengo una ferreteria',       'ferreteria-demo'],
            'farmacia'    => ['soy una botica',             'demofarma'],
            'veterinaria' => ['vendo alimento para perro',  'demovet'],
            'restaurante' => ['tengo una polleria',         'demopolleria'],
            'licoreria'   => ['vendo licores y cerveza',    'demolicor'],
            'boutique'    => ['vendo ropa',                 'demoboutique'],
        ];
    }

    /** @dataProvider rubros */
    public function test_cada_rubro_recibe_su_demo(string $loQueEscribe, string $slugEsperado): void
    {
        $r = $this->conversar(['hola', 'btn:rubro', $loQueEscribe]);

        $this->assertStringContainsString("arindg.com/$slugEsperado", $r,
            "Escribiendo \"$loQueEscribe\" el bot no ofrecio la demo $slugEsperado.");
    }

    public function test_el_cliente_de_abarrotes_ya_no_ve_una_ferreteria(): void
    {
        // El caso real de la conversacion 128.
        $r = $this->conversar(['hola', 'btn:rubro', 'vendo galletas agua gaseosas']);

        $this->assertStringContainsString('arindg.com/demomarket', $r);
        $this->assertStringNotContainsString('ferreteria-demo', $r,
            'FUGA: un cliente de abarrotes volvio a recibir la demo de ferreteria.');
    }

    /**
     * "Otro rubro" mandaba la demo de FERRETERIA.
     *
     * El botón apuntaba al router, y el router clasifica EL TEXTO del
     * mensaje. Al pulsarlo llega "Otro rubro", que no coincide con ningún
     * rubro, así que caía en la rama por defecto: la ferretería. Visto en una
     * conversación real el 2026-09-22.
     */
    public function test_otro_rubro_pregunta_no_manda_la_ferreteria(): void
    {
        $r = $this->conversar(['hola', 'btn:rubro', 'btn:rubro_otro']);

        $this->assertStringNotContainsString('ferreteria-demo', $r,
            'FUGA: "Otro rubro" volvió a mandar la demo de ferretería.');
        $this->assertStringContainsString('qué vendes', $r,
            'Debe preguntar el rubro por escrito.');
    }

    public function test_tras_otro_rubro_lo_escrito_si_enruta(): void
    {
        $r = $this->conversar(['hola', 'btn:rubro', 'btn:rubro_otro', 'vendo licores']);

        $this->assertStringContainsString('arindg.com/demolicor', $r);
    }

    /**
     * Un rubro sin demo propia recibe un ejemplo, pero DICIENDO que no es de
     * lo suyo. Mandar una ferretería a quien vende paneles solares, como si
     * fuera su rubro, parece que el bot no lo escuchó.
     */
    public function test_un_rubro_desconocido_lo_dice_en_vez_de_fingir(): void
    {
        $r = $this->conversar(['hola', 'btn:rubro', 'btn:rubro_otro', 'vendo paneles solares']);

        $this->assertStringContainsString('Todavía no tengo una tienda de ejemplo de tu rubro', $r);
        $this->assertStringContainsString('asesor', $r,
            'Sin demo de su rubro, la salida es un asesor que se la prepare.');
    }

    public function test_ver_precios_muestra_el_detalle_en_un_solo_paso(): void
    {
        $r = $this->conversar(['hola', 'btn:precios']);

        // Los 3 planes con lo que incluye cada uno, sin un segundo paso.
        $this->assertStringContainsString('490', $r);
        $this->assertStringContainsString('590', $r);
        $this->assertStringContainsString('690', $r);
        $this->assertStringContainsString('dominio propio', $r,
            'El detalle debe verse ya: "Comparar planes" repetia lo mismo en otro paso.');
        $this->assertStringNotContainsString('Comparar planes', $r,
            'Ese boton sobra: el detalle ya se mostro.');
    }

    public function test_las_siete_demos_estan_declaradas(): void
    {
        $demos = array_filter(array_keys($this->def['bloques']),
            fn ($k) => str_starts_with($k, 'demo_'));

        $this->assertCount(7, $demos);
    }
}
