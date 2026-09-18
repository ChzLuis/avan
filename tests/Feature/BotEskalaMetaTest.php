<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Models\BotFlow;
use App\Modules\Crm\Models\WaCanal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El flujo REAL de Eskala (bot_builder_flows 17, copia en tests/Fixtures) recorrido
 * de punta a punta por WhatsApp oficial (Meta) y por el conector QR (Baileys):
 * botones de atajo, boton con enlace, toques, si/no escrito y texto plano.
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
        // Meta responde ok; cualquier IA responde un texto fijo (no se llama en los caminos por boton).
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

    private function resumen(array $d): string
    {
        return match ($d['type']) {
            'text'        => 'text:' . mb_substr($d['text']['body'], 0, 25),
            'image'       => 'image',
            'interactive' => $d['interactive']['type'] . ':'
                . implode('|', array_map(fn ($b) => $b['reply']['id'], $d['interactive']['action']['buttons'] ?? []))
                . ($d['interactive']['action']['parameters']['url'] ?? ''),
            default       => $d['type'],
        };
    }

    private function sinIa(string $porque): void
    {
        Http::assertNotSent(fn ($req) => ! str_contains($req->url(), 'graph.facebook.com'), $porque);
    }

    public function test_hola_termina_en_la_pregunta_del_rubro_con_tres_botones(): void
    {
        $this->meta($this->texto('hola'));
        $env = $this->enviados();
        $r = array_map(fn ($d) => $this->resumen($d), $env);

        $this->assertStringStartsWith('text:', $r[0], 'Saludo primero');
        $this->assertContains('image', $r, 'El flyer sale como imagen');
        $this->assertSame('button:btn:mas_ejemplos|btn:demo|btn:asesor', end($r), 'La pregunta del rubro cierra con los 3 atajos');
        $this->assertStringContainsString('qué negocio tienes', end($env)['interactive']['body']['text']);
        $this->sinIa('Hasta aqui no hace falta IA');
    }

    public function test_tocar_ver_tienda_demo_manda_el_enlace_y_los_botones_de_la_demo(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:demo'));
        $env = $this->enviados();
        $r = array_map(fn ($d) => $this->resumen($d), $env);
        $n = count($r);

        $this->assertSame('cta_url:https://arindg.com/ferreteria-demo', $r[$n - 2]);
        $this->assertSame('Abrir la demo', $env[$n - 2]['interactive']['action']['parameters']['display_text']);
        $this->assertSame('button:btn:asesor|btn:sin_prisa', $r[$n - 1]);
        $this->assertSame('¿Te preparo una demo con el rubro de tu negocio?', $env[$n - 1]['interactive']['body']['text']);
        $this->sinIa('El toque no pasa por la IA');
    }

    public function test_en_la_demo_tocar_si_quiero_va_al_asesor(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:demo'));
        $antes = count($this->enviados());
        $this->meta($this->toque('btn:asesor'));
        $nuevos = array_slice($this->enviados(), $antes);

        $this->assertNotEmpty($nuevos);
        $this->assertSame('text', $nuevos[0]['type']);
        $this->assertStringContainsString('asesor', mb_strtolower($nuevos[0]['text']['body']));
        $this->sinIa('Los toques no pasan por la IA');
    }

    public function test_en_la_demo_escribir_no_sigue_funcionando_por_si_no(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:demo'));
        $antes = count($this->enviados());
        $this->meta($this->texto('no'));
        $nuevos = array_slice($this->enviados(), $antes);

        $this->assertNotEmpty($nuevos, 'Un "no" escrito sigue llevando a sin_prisa');
        $this->assertSame('text', $nuevos[0]['type']);
        $this->assertStringNotContainsString('asesor', mb_strtolower($nuevos[0]['text']['body']));
    }

    public function test_tocar_hablar_con_asesor_desde_la_pregunta_del_rubro(): void
    {
        $this->meta($this->texto('hola'));
        $antes = count($this->enviados());
        $this->meta($this->toque('btn:asesor'));
        $nuevos = array_slice($this->enviados(), $antes);

        $this->assertNotEmpty($nuevos);
        $this->assertSame('text', $nuevos[0]['type']);
        $this->assertStringContainsString('asesor', mb_strtolower($nuevos[0]['text']['body']));
        $this->sinIa('El toque salta directo');
    }

    public function test_tocar_ver_mas_ejemplos_manda_imagenes_y_cierra_con_botones(): void
    {
        $this->meta($this->texto('hola'));
        $antes = count($this->enviados());
        $this->meta($this->toque('btn:mas_ejemplos'));
        $r = array_map(fn ($d) => $this->resumen($d), array_slice($this->enviados(), $antes));

        $this->assertContains('image', $r);
        $this->assertSame('button:btn:demo|btn:asesor|btn:sin_prisa', end($r));
    }

    public function test_escribir_el_rubro_en_vez_de_tocar_sigue_teniendo_respuesta(): void
    {
        $this->meta($this->texto('hola'));
        $antes = count($this->enviados());
        $this->meta($this->texto('tengo una ferretería con 300 productos'));
        $nuevos = array_slice($this->enviados(), $antes);

        // Sin clave de IA el clasificador local sigue vivo: responde y no se cuela ningun id de boton.
        $this->assertNotEmpty($nuevos, 'El texto libre sigue teniendo respuesta');
        $this->assertStringNotContainsString('btn:', json_encode(array_map(fn ($d) => $d['text']['body'] ?? '', $nuevos)));
    }

    public function test_un_boton_que_no_existe_no_rompe(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:no_existe'));
        $this->assertTrue(true);
    }

    public function test_por_el_conector_qr_los_botones_salen_como_texto_plano(): void
    {
        $token = $this->proyecto->fresh()->copilot_token;
        $r = $this->postJson('/api/bot/inbound', ['telefono' => '51933333333', 'mensaje' => 'hola', 'nombre' => 'QR'], ['X-Copilot-Token' => $token])->assertOk();
        $respuestas = $r->json('respuestas');
        $ultima = end($respuestas);

        $this->assertIsString($ultima, 'Por Baileys la pregunta con botones vuelve a texto');
        $this->assertStringContainsString('qué negocio tienes', $ultima);
        $this->assertStringNotContainsString('btn:', json_encode($respuestas));
        $this->assertStringNotContainsString('cta_url', json_encode($respuestas));
    }
}
