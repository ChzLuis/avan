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
 * Guion v5: menu comercial (precios / tienda / asesor) -> interes -> "quiero" -> negocio y
 * fotos -> cierre humano. El cliente decide por donde empezar. Determinista: no necesita IA.
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
                . implode('|', array_map(fn ($f) => $f['id'], $d['interactive']['action']['sections'][0]['rows'] ?? []))
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

    public function test_hola_abre_con_el_menu_comercial_sin_preguntas(): void
    {
        $this->meta($this->texto('hola'));
        $env = $this->enviados();

        $this->assertCount(1, $env, 'Una sola burbuja');
        $this->assertSame('button:btn:precios|btn:tienda|btn:asesor', $this->resumen($env[0]));
        $this->assertStringContainsString('Qué te gustaría ver', $this->cuerpo($env[0]));
        $this->assertSame('💰 Ver precios', $env[0]['interactive']['action']['buttons'][0]['reply']['title']);
        $this->sinIa();
    }

    public function test_ver_precios_da_los_tres_planes_directo(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:precios'));
        $env = $this->nuevos($n);

        $this->assertCount(2, $env, 'Primero el flyer, luego el texto con botones');
        $this->assertSame('image', $this->resumen($env[0]));
        $this->assertStringContainsString('planes.jpg', $env[0]['image']['link']);
        $this->assertStringContainsString('Start — S/ 490', $this->cuerpo($env[1]));
        $this->assertStringContainsString('Business — S/ 690', $this->cuerpo($env[1]));
        $this->assertSame('button:btn:comparar|btn:tienda|btn:quiero', $this->resumen($env[1]));
        $this->sinIa();
    }

    public function test_comparar_planes_manda_el_flyer_con_los_topes_correctos(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:precios'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:comparar'));
        $env = $this->nuevos($n);

        $this->assertSame('button:btn:quiero|btn:tienda|btn:dudas', $this->resumen($env[0]), 'El flyer ya se vio en precios');
        $this->assertStringContainsString('Hasta 100 productos', $this->cuerpo($env[0]));
        $this->assertStringContainsString('catálogo PDF', $this->cuerpo($env[0]));
    }

    public function test_ver_una_tienda_muestra_la_demo_y_luego_el_desde_con_cta(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:tienda'));
        $env = $this->nuevos($n);

        $this->assertCount(2, $env);
        $this->assertSame('cta_url+image:https://arindg.com/ferreteria-demo', $this->resumen($env[0]));
        $this->assertStringContainsString('Así puede verse tu tienda', $this->cuerpo($env[0]));
        $this->assertSame('button:btn:precios|btn:quiero|btn:dudas', $this->resumen($env[1]));
        $this->assertStringContainsString('desde *S/ 490*', $this->cuerpo($env[1]));
        $this->sinIa();
    }

    public function test_hablar_con_asesor_corta_el_bot_y_abre_el_trato(): void
    {
        Productos::activar($this->proyecto, 'crm');
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:asesor'));
        $env = $this->nuevos($n);

        $this->assertCount(1, $env, 'Sin mas bot');
        $this->assertStringContainsString('Te paso con un asesor', $this->cuerpo($env[0]));
        $this->assertCount(1, CrmTrato::where('project_id', $this->proyecto->id)->get());
    }

    public function test_quiero_una_pregunta_el_negocio_luego_las_fotos_y_cierra_con_trato(): void
    {
        Productos::activar($this->proyecto, 'crm');
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:tienda'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:quiero'));
        $env = $this->nuevos($n);
        $this->assertCount(1, $env);
        $this->assertStringContainsString('Qué tipo de negocio tienes', $this->cuerpo($env[0]), 'Recien ahora hay motivo para preguntar');
        $this->assertCount(1, CrmTrato::where('project_id', $this->proyecto->id)->get(), 'El interes real abre el trato');

        $n = count($this->enviados());
        $this->meta($this->texto('una boutique de ropa'));
        $env = $this->nuevos($n);
        $this->assertSame('button:btn:cierre|btn:cierre#2', $this->resumen($env[0]));
        $this->assertStringContainsString('fotos listas', $this->cuerpo($env[0]));

        $n = count($this->enviados());
        $this->meta($this->toque('btn:cierre#2'));
        $env = $this->nuevos($n);
        $this->assertStringContainsString('nombre de tu negocio', $this->cuerpo($env[0]));

        $this->meta($this->texto('Boutique Rosa, @boutiquerosa'));
        $this->assertCount(1, CrmTrato::where('project_id', $this->proyecto->id)->get(), 'No se duplica');
        $this->sinIa();
    }

    public function test_sin_producto_crm_no_crea_tratos(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:quiero'));
        $this->assertSame(0, CrmTrato::count());
    }

    public function test_cada_intencion_escrita_va_directo_a_lo_suyo(): void
    {
        // Precio directo, sin apertura.
        $this->meta($this->texto('precio?'));
        $env = $this->enviados();
        $this->assertCount(2, $env);
        $this->assertStringContainsString('Start — S/ 490', $this->cuerpo($env[1]));

        // Ejemplos -> demo. Que incluye -> comparar. Quiero comprar -> quiero.
        $casos = [
            ['quiero ver ejemplos', 'cta_url+image:https://arindg.com/ferreteria-demo'],
            ['qué incluye?', 'button:btn:quiero|btn:tienda|btn:dudas'],
            ['quiero comprar', 'text'],
        ];
        foreach ($casos as $j => [$msg, $esperado]) {
            $n = count($this->enviados());
            $this->meta($this->texto($msg), '5190000000' . ($j + 2));
            $env = $this->nuevos($n);
            $this->assertSame($esperado, $this->resumen($env[0]), $msg);
        }
        $this->sinIa();
    }

    public function test_texto_que_no_se_entiende_vuelve_al_menu(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->texto('asdfgh'));
        $env = $this->nuevos($n);
        $this->assertSame('button:btn:precios|btn:tienda|btn:asesor', $this->resumen(end($env)));
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
        $this->assertStringContainsString('Qué te gustaría ver', end($respuestas));

        $r = $this->postJson('/api/bot/inbound', ['telefono' => '51933333333', 'mensaje' => 'btn:tienda', 'nombre' => 'QR'], ['X-Copilot-Token' => $token])->assertOk();
        $todo = $r->json('respuestas');
        $this->assertSame('imagen', $todo[0]['tipo'], 'La demo con foto vuelve a ser imagen con el enlace de pie');
        $this->assertStringContainsString('https://arindg.com/ferreteria-demo', $todo[0]['caption']);
        $this->assertStringNotContainsString('btn:', json_encode($todo));
    }
}
