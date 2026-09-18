<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Models\BotFlow;
use App\Modules\Crm\Models\CrmTrato;
use App\Modules\Crm\Models\WaCanal;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El flujo REAL de Eskala (bot_builder_flows 17, copia en tests/Fixtures) recorrido
 * de punta a punta por WhatsApp oficial (Meta) y por el conector QR (Baileys).
 * Guion v4: ¿que vendes? -> ¿cuantos productos? -> plan + demo -> precio + CTA -> cierre
 * humano (abre el trato). Tres decisiones del cliente. Determinista: no necesita IA.
 */
class BotEskalaMetaTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proyecto = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Eskala', 'slug' => 'eskala-' . uniqid(), 'is_active' => true]);
        WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'Eskala', 'tipo' => 'bixo', 'phone_number_id' => '777', 'access_token' => 't', 'app_secret' => 'sec', 'verify_token' => 'v', 'activo' => true]);
        $def = json_decode(file_get_contents(base_path('tests/Fixtures/flujo_eskala_meta.json')), true);
        BotFlow::comercialDe($this->proyecto)->update(['activo' => true, 'definicion' => $def]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200),
            '*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta IA']]]], 200),
        ]);
    }

    private function meta(array $mensaje, string $de = '51900000001'): void
    {
        $cuerpo = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '777'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => $de]],
            'messages' => [array_merge(['from' => $de, 'id' => 'wamid.' . uniqid()], $mensaje)],
        ]]]]]]);

        $this->call('POST', '/api/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $cuerpo, 'sec'),
        ], $cuerpo)->assertOk();
    }

    private function texto(string $t): array
    {
        return ['type' => 'text', 'text' => ['body' => $t]];
    }

    private function toque(string $id): array
    {
        return ['type' => 'interactive', 'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => $id, 'title' => 'x']]];
    }

    /** Lo enviado a Meta, en orden, solo mensajes (sin el "marcar leido"). */
    private function enviados(): array
    {
        $out = [];
        Http::recorded(function ($req) use (&$out) {
            if (str_contains($req->url(), 'graph.facebook.com') && ($req->data()['status'] ?? '') !== 'read') {
                $out[] = $req->data();
            }
        });

        return $out;
    }

    /** Solo lo enviado a partir de la posicion $desde. */
    private function nuevos(int $desde): array
    {
        return array_values(array_slice($this->enviados(), $desde));
    }

    private function resumen(array $d): string
    {
        return match ($d['type']) {
            'text'        => 'text',
            'image'       => 'image',
            'interactive' => $d['interactive']['type']
                . (isset($d['interactive']['header']) ? '+' . $d['interactive']['header']['type'] : '')
                . ':' . implode('|', array_map(fn ($b) => $b['reply']['id'], $d['interactive']['action']['buttons'] ?? []))
                . ($d['interactive']['action']['parameters']['url'] ?? ''),
            default       => $d['type'],
        };
    }

    private function cuerpo(array $d): string
    {
        return $d['text']['body'] ?? $d['interactive']['body']['text'] ?? $d['image']['caption'] ?? '';
    }

    private function sinIa(): void
    {
        Http::assertNotSent(fn ($req) => ! str_contains($req->url(), 'graph.facebook.com'), 'Este camino no necesita IA');
    }

    public function test_hola_abre_con_que_vendes_y_tres_rubros(): void
    {
        $this->meta($this->texto('hola'));
        $env = $this->enviados();

        $this->assertCount(1, $env, 'Una sola burbuja');
        $this->assertSame('button:btn:cantidad|btn:cantidad#2|btn:cantidad#3', $this->resumen($env[0]));
        $this->assertStringContainsString('qué vendes', $this->cuerpo($env[0]));
        $this->assertStringNotContainsString('S/', $this->cuerpo($env[0]));
        $this->assertSame('👕 Ropa', $env[0]['interactive']['action']['buttons'][0]['reply']['title']);
        $ids = array_map(fn ($b) => $b['reply']['id'], $env[0]['interactive']['action']['buttons']);
        $this->assertCount(3, array_unique($ids), 'Meta rechaza (#131009) botones con el mismo id');
        $this->sinIa();
    }

    public function test_el_rubro_lleva_a_cuantos_productos_con_tres_rangos(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:cantidad#3'));
        $env = $this->nuevos($n);

        $this->assertCount(1, $env, 'El sufijo #3 del boton "Otros" no estorba');
        $this->assertSame('button:btn:plan_start|btn:plan_pro|btn:plan_business', $this->resumen($env[0]));
        $this->assertStringContainsString('Cuántos productos', $this->cuerpo($env[0]));
        $this->sinIa();
    }

    public function test_de_50_a_150_recomienda_pro_muestra_la_demo_y_da_precio_con_cta(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:cantidad'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:plan_pro'));
        $env = $this->nuevos($n);

        $this->assertCount(2, $env, 'Recomendacion + demo, y precio + CTA');
        $this->assertSame('cta_url:https://arindg.com/ferreteria-demo', $this->resumen($env[0]));
        $this->assertStringContainsString('*PRO*', $this->cuerpo($env[0]));
        $this->assertSame('👀 Ver tienda demo', $env[0]['interactive']['action']['parameters']['display_text']);
        $this->assertSame('button:btn:cierre_pro|btn:planes_pro|btn:dudas', $this->resumen($env[1]));
        $this->assertStringContainsString('S/ 590', $this->cuerpo($env[1]));
        $this->assertSame('🚀 Quiero empezar', $env[1]['interactive']['action']['buttons'][0]['reply']['title']);
        $this->sinIa();
    }

    public function test_ver_planes_manda_el_flyer_con_los_tres_precios_y_vuelve_al_cta(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:cantidad'));
        $this->meta($this->toque('btn:plan_start'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:planes_start'));
        $env = $this->nuevos($n);

        $this->assertCount(1, $env);
        $this->assertSame('button+image:btn:cierre_start|btn:demo_start|btn:dudas', $this->resumen($env[0]));
        $this->assertStringContainsString('START S/ 490 · PRO S/ 590 · BUSINESS S/ 690', $this->cuerpo($env[0]));
        $this->assertStringContainsString('S/ 100 al año', $this->cuerpo($env[0]));
    }

    public function test_quiero_empezar_abre_el_trato_con_el_valor_del_plan_y_pide_los_datos(): void
    {
        Productos::activar($this->proyecto, 'crm');
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:cantidad'));
        $this->meta($this->toque('btn:plan_business'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:cierre_business'));
        $env = $this->nuevos($n);

        $this->assertStringContainsString('nombre de tu negocio', $this->cuerpo($env[0]));
        $t = CrmTrato::where('project_id', $this->proyecto->id)->get();
        $this->assertCount(1, $t);
        $this->assertEquals(690, $t[0]->valor, 'El trato nace con el valor del plan elegido');
        $this->assertSame('bot', $t[0]->origen);
        $this->assertSame('Nuevo', $t[0]->etapa->nombre);
        $this->assertNotNull($t[0]->wa_conversacion_id);

        $this->meta($this->texto('Boutique Rosa, @boutiquerosa'));
        $this->assertCount(1, CrmTrato::where('project_id', $this->proyecto->id)->get(), 'Los datos no abren otro trato');
    }

    public function test_sin_producto_crm_el_cierre_no_crea_tratos(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:cantidad'));
        $this->meta($this->toque('btn:plan_start'));
        $this->meta($this->toque('btn:cierre_start'));
        $this->assertSame(0, CrmTrato::count());
    }

    public function test_preguntar_el_precio_se_responde_al_toque_y_sigue_el_guion(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->texto('cuánto cuesta?'));
        $env = $this->nuevos($n);

        $this->assertCount(1, $env);
        $this->assertStringContainsString('S/ 490', $this->cuerpo($env[0]));
        $this->assertSame('button:btn:cantidad|btn:cantidad#2|btn:cantidad#3', $this->resumen($env[0]), 'Da el precio y vuelve a preguntar que vende');
        $this->sinIa();
    }

    public function test_el_primer_mensaje_ya_puede_traer_la_intencion(): void
    {
        $this->meta($this->texto('hola, cuanto cuesta la tienda virtual?'));
        $env = $this->enviados();

        $this->assertCount(1, $env, 'No manda ademas la apertura');
        $this->assertStringContainsString('S/ 490', $this->cuerpo($env[0]));
    }

    public function test_escribir_el_rubro_en_vez_de_tocar_avanza_a_la_cantidad(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->texto('tengo una ferretería'));
        $env = $this->nuevos($n);

        $this->assertCount(1, $env);
        $this->assertSame('button:btn:plan_start|btn:plan_pro|btn:plan_business', $this->resumen($env[0]));
        $this->sinIa();
    }

    public function test_la_demo_escrita_y_el_no_siguen_funcionando(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->texto('quiero ver la demo'));
        $env = $this->nuevos($n);
        $this->assertSame('cta_url:https://arindg.com/ferreteria-demo', $this->resumen($env[0]));
        $this->assertSame('button:btn:cierre_pro|btn:sin_prisa', $this->resumen($env[1]));

        $n = count($this->enviados());
        $this->meta($this->texto('no'));
        $env = $this->nuevos($n);
        $this->assertStringContainsString('Sin problema', $this->cuerpo($env[0]));
    }

    public function test_un_boton_que_no_existe_no_rompe(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:no_existe'));
        $this->assertTrue(true);
    }

    public function test_por_el_conector_qr_todo_sale_como_texto_o_imagen(): void
    {
        $token = $this->proyecto->fresh()->copilot_token;
        $r = $this->postJson('/api/bot/inbound', ['telefono' => '51933333333', 'mensaje' => 'hola', 'nombre' => 'QR'], ['X-Copilot-Token' => $token])->assertOk();
        $respuestas = $r->json('respuestas');
        $this->assertIsString(end($respuestas));
        $this->assertStringContainsString('qué vendes', end($respuestas));

        $this->postJson('/api/bot/inbound', ['telefono' => '51933333333', 'mensaje' => 'btn:cantidad', 'nombre' => 'QR'], ['X-Copilot-Token' => $token])->assertOk();
        $r = $this->postJson('/api/bot/inbound', ['telefono' => '51933333333', 'mensaje' => 'btn:plan_pro', 'nombre' => 'QR'], ['X-Copilot-Token' => $token])->assertOk();
        $todo = json_encode($r->json('respuestas'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('https://arindg.com/ferreteria-demo', $todo, 'El enlace va en el texto');
        $this->assertStringNotContainsString('btn:', $todo);
    }
}
