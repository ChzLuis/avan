<?php

namespace Tests\Feature;

use App\Modules\Bots\Ia\InterpreteComercial;
use App\Models\Category;
use App\Models\Module;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use App\Modules\Bots\Support\FlowEngine\PlantillaComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * La arquitectura híbrida del Bot Comercial: un solo motor, IA opcional por
 * proyecto. Lo crítico que se protege: si la IA desaparece por CUALQUIER
 * motivo, el bot sigue atendiendo por el motor estándar con el mensaje
 * original — y una empresa sin licencia jamás genera consumo de proveedor.
 */
class BotIaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Proveedor de prueba: OpenAI simulado con Http::fake — así se puede
        // afirmar "no se llamó al proveedor" con Http::assertNothingSent().
        config(['ia.provider' => 'openai']);
        config(['ia.providers.openai' => ['key' => 'sk-test', 'model' => 'gpt-test']]);

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Tienda Híbrida',
            'slug'      => 'tienda-hibrida',
            'is_active' => true,
            'address'   => 'Av. Central 456, Lima',
        ]);

        $cat = Category::create(['project_id' => $this->project->id, 'name' => 'Monitores']);
        Product::create([
            'project_id' => $this->project->id, 'category_id' => $cat->id,
            'name' => 'Monitor Samsung 24 FHD', 'price' => 549.00, 'stock' => 5,
        ]);

        $this->project->settings()->create(['key' => 'payment_yape_number', 'value' => '999 111 222']);
    }

    private function licenciar(Project $p, bool $activar = true): void
    {
        $modulo = Module::firstOrCreate(['key' => InterpreteComercial::MODULO], ['name' => 'Bot IA', 'is_active' => true]);
        $p->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
        if ($activar) {
            $p->settings()->updateOrCreate(['key' => InterpreteComercial::AJUSTE], ['value' => '1']);
        }
        $p->refresh();
    }

    /** Respuesta simulada del proveedor con el contrato que devolvería la IA. */
    private function iaResponde(string $contenido): void
    {
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => $contenido]]],
        ])]);
    }

    private function conversar(array $mensajes, ?Project $p = null): string
    {
        $p = $p ?? $this->project;
        $runner = new FlowRunner($p, PlantillaComercial::definicion($p));
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];
        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, '51988000222');
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $salida[] = is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    // ── 1. Sin IA: motor estándar y CERO consumo ─────────────────────────────

    public function test_proyecto_sin_licencia_no_toca_al_proveedor(): void
    {
        Http::fake();

        $txt = $this->conversar(['hola', 'monitor samsung 24']);

        $this->assertStringContainsString('*Monitores*', $txt);
        Http::assertNothingSent();
    }

    public function test_licenciado_pero_apagado_tampoco_consume(): void
    {
        $this->licenciar($this->project, activar: false);
        Http::fake();

        $txt = $this->conversar(['hola', 'monitor samsung 24']);

        $this->assertStringContainsString('*Monitores*', $txt);
        Http::assertNothingSent();
        $this->assertFalse(InterpreteComercial::habilitado($this->project));
    }

    /** Activar sin licencia está prohibido incluso forzando el setting. */
    public function test_el_setting_solo_no_habilita_sin_licencia(): void
    {
        $this->project->settings()->create(['key' => InterpreteComercial::AJUSTE, 'value' => '1']);

        $this->assertFalse(InterpreteComercial::habilitado($this->project->fresh()));
    }

    // ── 2. Con IA: interpreta y el motor consulta datos reales ──────────────

    public function test_mensaje_natural_se_interpreta_y_busca_datos_reales(): void
    {
        $this->licenciar($this->project);
        $this->iaResponde('{"intent":"product_search","consulta":"monitor samsung 24"}');

        $txt = $this->conversar(['hola', 'hola amigo ando por la oficina y me urge conseguir una pantalla samung de unas veinticuatro pulgadas para renderizar']);

        $this->assertStringContainsString('*Monitores*', $txt, 'La consulta normalizada por la IA encuentra el producto real.');
        $this->assertStringContainsString('*Monitores*', $txt, 'El precio sale de la BD, no de la IA.');
        Http::assertSentCount(1);
    }

    // ── 3-7. Degradación: la IA desaparece y el bot sigue ────────────────────

    public function test_timeout_del_proveedor_cae_al_motor_estandar(): void
    {
        $this->licenciar($this->project);
        Http::fake(['api.openai.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

        $txt = $this->conversar(['hola', 'hola amigo ando por la oficina y me urge conseguir una pantalla samung de unas veinticuatro pulgadas para renderizar']);

        $this->assertNotSame('', trim($txt), 'El cliente recibe respuesta del motor estándar.');
        $this->assertStringNotContainsString('timeout', mb_strtolower($txt), 'Nada técnico llega al cliente.');
        $this->assertStringNotContainsString('openai', mb_strtolower($txt));
    }

    public function test_json_roto_cae_al_motor_estandar(): void
    {
        $this->licenciar($this->project);
        $this->iaResponde('esto no es json {{{');

        $txt = $this->conversar(['hola', 'hola amigo ando por la oficina y me urge conseguir una pantalla samung de unas veinticuatro pulgadas para renderizar']);

        $this->assertNotSame('', trim($txt));
        $this->assertStringNotContainsString('json', mb_strtolower($txt));
    }

    public function test_intent_desconocido_se_rechaza(): void
    {
        $this->licenciar($this->project);
        $this->iaResponde('{"intent":"invent_product","consulta":"lo que sea"}');

        $this->assertNull(InterpreteComercial::validar('{"intent":"invent_product","consulta":"x"}'));

        $txt = $this->conversar(['hola', 'hola amigo ando por la oficina y me urge conseguir una pantalla samung de unas veinticuatro pulgadas para renderizar']);
        $this->assertNotSame('', trim($txt), 'Rechazado el intent, el motor estándar responde.');
    }

    public function test_proveedor_caido_500_cae_al_motor_estandar(): void
    {
        $this->licenciar($this->project);
        Http::fake(['api.openai.com/*' => Http::response('boom', 500)]);

        $txt = $this->conversar(['hola', 'hola amigo ando por la oficina y me urge conseguir una pantalla samung de unas veinticuatro pulgadas para renderizar']);

        $this->assertNotSame('', trim($txt));
    }

    public function test_sin_api_key_ni_siquiera_llama(): void
    {
        $this->licenciar($this->project);
        config(['ia.providers.openai.key' => null]);
        Http::fake();

        $txt = $this->conversar(['hola', 'monitor samsung 24']);

        $this->assertStringContainsString('*Monitores*', $txt);
        Http::assertNothingSent();
    }

    /** La IA no puede colar un precio: los campos extra del contrato se ignoran. */
    public function test_un_precio_inventado_por_la_ia_no_llega_al_cliente(): void
    {
        $this->licenciar($this->project);
        $this->iaResponde('{"intent":"product_search","consulta":"monitor samsung 24","producto":{"nombre":"Monitor Samsung","precio":99.00},"precio":99.00}');

        $contrato = InterpreteComercial::validar('{"intent":"product_search","consulta":"x","precio":99.00}');
        $this->assertArrayNotHasKey('precio', $contrato, 'El contrato solo conserva intent/ruta/consulta.');

        $txt = $this->conversar(['hola', 'hola amigo ando por la oficina y me urge conseguir una pantalla samung de unas veinticuatro pulgadas para renderizar']);
        $this->assertStringContainsString('*Monitores*', $txt, 'El precio es el de la BD.');
        $this->assertStringNotContainsString('99.00', $txt, 'El precio inventado jamás se muestra.');
    }

    // ── 8-9. Las reglas de siempre no gastan IA ──────────────────────────────

    public function test_opciones_de_menu_y_frases_claras_no_llaman_ia(): void
    {
        $this->licenciar($this->project);
        Http::fake();

        // Opción de lista, pago por diccionario y consulta clara de producto.
        $txt = $this->conversar(['hola', '1', 'aceptan yape', 'precio monitor samsung']);

        $this->assertStringContainsString('999 111 222', $txt);
        $this->assertStringContainsString('*Monitores*', $txt);
        Http::assertNothingSent();
    }

    public function test_handoff_sigue_intacto_con_ia_activa(): void
    {
        $this->licenciar($this->project);
        Http::fake();

        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = ['bloque' => 'menu', 'vars' => [], 'esperando' => true];
        $res = $runner->procesar('quiero hablar con una persona', $estado, '51988000222');

        $this->assertSame('pide-asesor', $res['acciones']['registrar']['etiqueta'] ?? null);
        Http::assertNothingSent();
    }

    // ── 10. Multi-tenant ─────────────────────────────────────────────────────

    public function test_aislamiento_entre_proyecto_con_ia_y_sin_ia(): void
    {
        $conIa = $this->project;
        $this->licenciar($conIa);

        $sinIa = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Clásica', 'slug' => 'tienda-clasica', 'is_active' => true,
        ]);
        Product::create([
            'project_id' => $sinIa->id,
            'name' => 'Taladro Bosch 500W', 'price' => 299.00,
        ]);

        $this->assertTrue(InterpreteComercial::habilitado($conIa));
        $this->assertFalse(InterpreteComercial::habilitado($sinIa));

        // El proyecto sin IA atiende por reglas y no consume nada.
        Http::fake();
        $txt = $this->conversar(['hola', 'precio taladro bosch'], $sinIa);
        $this->assertStringContainsString('No encontré una categoría', $txt);
        $this->assertStringNotContainsString('Monitor Samsung', $txt, 'Jamás ve productos de otro proyecto.');
        Http::assertNothingSent();
    }
}
