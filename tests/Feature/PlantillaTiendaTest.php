<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use App\Modules\Bots\Support\FlowEngine\PlantillaTienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bot estándar de tienda.
 *
 * No comprueba que el JSON «tenga buena pinta»: recorre la conversación completa
 * contra el motor real, como la haría un cliente. Un flujo que se ve bien en el
 * lienzo pero se atasca en el segundo paso no sirve de nada.
 */
class PlantillaTiendaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Tienda Demo',
            'slug'      => 'tienda-demo',
            'is_active' => true,
        ]);

        $cat = Category::create(['project_id' => $this->project->id, 'name' => 'Abarrotes', 'is_active' => true]);
        Product::create([
            'project_id' => $this->project->id, 'category_id' => $cat->id,
            'name' => 'Arroz Costeño 5kg', 'price' => 25.90, 'stock' => 40, 'is_available' => true,
        ]);
    }

    /** Ejecuta un mensaje del cliente y devuelve [respuestas, estado]. */
    private function hablar(array $definicion, string $mensaje, array $estado): array
    {
        $runner = new FlowRunner($this->project, $definicion);
        $r = $runner->procesar($mensaje, $estado, '51999888777');

        return [$r['respuestas'], $r['estado'], $r['acciones'] ?? []];
    }

    private function textoDe(array $respuestas): string
    {
        return collect($respuestas)->map(
            fn ($r) => is_array($r) ? json_encode($r, JSON_UNESCAPED_UNICODE) : (string) $r
        )->implode(' | ');
    }

    public function test_la_plantilla_solo_usa_bloques_que_el_motor_entiende(): void
    {
        $soportados = [
            'mensaje', 'pregunta', 'opciones', 'lista', 'buscar_producto', 'ia', 'imagen',
            'archivo', 'condicion', 'webhook', 'espera', 'catalogo', 'estado_pedido',
            'cotizar', 'registrar_crm', 'agendar', 'categorias', 'buscar_agregar',
            'ver_carrito', 'asistente', 'pago_qr', 'fin',
        ];

        foreach (PlantillaTienda::definicion($this->project)['bloques'] as $id => $b) {
            $this->assertContains($b['tipo'], $soportados, "El bloque `{$id}` usa un tipo que el motor no ejecuta.");
        }
    }

    /** Ningún `siguiente` puede apuntar a un bloque que no existe. */
    public function test_ningun_camino_lleva_a_la_nada(): void
    {
        $bloques = PlantillaTienda::definicion($this->project)['bloques'];
        $ids = array_keys($bloques);

        foreach ($bloques as $id => $b) {
            foreach (['siguiente', 'finalizar_siguiente'] as $campo) {
                if (!empty($b[$campo])) {
                    $this->assertContains($b[$campo], $ids, "`{$id}.{$campo}` apunta a `{$b[$campo]}`, que no existe.");
                }
            }
            foreach ($b['opciones'] ?? [] as $op) {
                if (!empty($op['siguiente'])) {
                    $this->assertContains($op['siguiente'], $ids, "Una opción de `{$id}` apunta a `{$op['siguiente']}`, que no existe.");
                }
            }
            foreach ($b['secciones'] ?? [] as $sec) {
                foreach ($sec['filas'] ?? [] as $f) {
                    if (!empty($f['siguiente'])) {
                        $this->assertContains($f['siguiente'], $ids, "Una fila de `{$id}` apunta a `{$f['siguiente']}`, que no existe.");
                    }
                }
            }
        }
    }

    public function test_el_saludo_lleva_al_menu_con_sus_opciones(): void
    {
        $def = PlantillaTienda::definicion($this->project);

        [$respuestas, $estado] = $this->hablar($def, 'hola', ['bloque' => null, 'vars' => [], 'esperando' => false]);
        $texto = $this->textoDe($respuestas);

        $this->assertStringContainsString('Tienda Demo', $texto, 'El saludo no nombra al negocio.');
        $this->assertStringContainsString('Buscar un producto', $texto);
        $this->assertStringContainsString('Estado de mi pedido', $texto);
        $this->assertTrue($estado['esperando'], 'El menú debería quedar esperando la elección del cliente.');
    }

    /**
     * El recorrido que importa: de saludar a que quede un PEDIDO REAL.
     */
    public function test_un_cliente_puede_comprar_de_principio_a_fin(): void
    {
        $def = PlantillaTienda::definicion($this->project);
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];

        [, $estado] = $this->hablar($def, 'hola', $estado);                     // saludo + menú
        [, $estado] = $this->hablar($def, '🔎 Buscar un producto', $estado);    // elige buscar
        [$r, $estado] = $this->hablar($def, 'arroz', $estado);                  // busca

        $this->assertStringContainsString('Arroz', $this->textoDe($r), 'La búsqueda no encontró el producto del catálogo.');

        [, $estado] = $this->hablar($def, '1', $estado);                        // agrega el primero
        [$r, $estado] = $this->hablar($def, 'finalizar', $estado);              // cierra la compra

        $this->assertStringContainsString('dirección', mb_strtolower($this->textoDe($r)),
            'Tras finalizar debería pedir la dirección.');

        [, $estado] = $this->hablar($def, 'Av. Perú 123, cerca al parque', $estado);
        [$r, $estado, $acciones] = $this->hablar($def, '💵 Al recibir el pedido', $estado);

        $this->assertNotEmpty($acciones['pedido'] ?? null, 'No se generó el pedido real al cerrar la compra.');
        $this->assertSame('Av. Perú 123, cerca al parque', $acciones['pedido']['direccion']);
        $this->assertNotEmpty($acciones['pedido']['items'], 'El pedido salió sin productos.');
        $this->assertStringContainsString('Av. Perú 123', $this->textoDe($r), 'La confirmación no repite la dirección.');
    }

    /** Una dirección que no lo parece no debe colarse en el pedido. */
    public function test_no_acepta_cualquier_cosa_como_direccion(): void
    {
        $def = PlantillaTienda::definicion($this->project);
        $estado = ['bloque' => 'direccion', 'vars' => ['carrito' => [['nombre' => 'X', 'precio' => 1, 'cantidad' => 1]]], 'esperando' => true];

        [$r, $estado] = $this->hablar($def, 'sí', $estado);

        $this->assertSame('direccion', $estado['bloque'], 'Avanzó pese a una dirección inválida.');
        $this->assertStringContainsString('dirección', mb_strtolower($this->textoDe($r)));
    }

    /** Sin catálogo publicado, el bot orienta en vez de quedarse mudo. */
    public function test_sin_productos_no_deja_al_cliente_colgado(): void
    {
        Product::query()->delete();
        Category::query()->delete();

        $def = PlantillaTienda::definicion($this->project);
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];

        [, $estado] = $this->hablar($def, 'hola', $estado);
        [$r] = $this->hablar($def, '🛍️ Ver categorías', $estado);

        $this->assertNotEmpty($r, 'El bot se quedó mudo sin catálogo.');
        $this->assertStringContainsString('catálogo', mb_strtolower($this->textoDe($r)));
    }
}
