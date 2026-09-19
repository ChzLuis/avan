<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Models\BotFlow;
use App\Modules\Crm\Models\CrmTrato;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaMensaje;
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
            if ($req->method() === 'POST' && str_contains($req->url(), 'graph.facebook.com') && ($req->data()['status'] ?? '') !== 'read') {
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

    public function test_hola_abre_con_el_flyer_de_presentacion_y_el_menu_comercial(): void
    {
        $this->meta($this->texto('hola'));
        $env = $this->enviados();

        $this->assertCount(2, $env, 'Flyer con presentacion + menu');
        $this->assertSame('image', $this->resumen($env[0]));
        $this->assertStringContainsString('soy *Valeria* de Eskala', $this->cuerpo($env[0]));
        $this->assertStringContainsString('sin mensualidades', $this->cuerpo($env[0]));
        $this->assertSame('button:btn:precios|btn:tienda|btn:asesor', $this->resumen($env[1]));
        $this->assertStringContainsString('Qué te gustaría ver', $this->cuerpo($env[1]));
        $this->assertSame('💰 Ver precios', $env[1]['interactive']['action']['buttons'][0]['reply']['title']);
        $this->sinIa();
    }

    public function test_una_conversacion_abandonada_horas_atras_empieza_de_nuevo(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:tienda'));
        $this->meta($this->toque('btn:quiero')); // esperando "¿que tipo de negocio tienes?"
        \App\Modules\Bots\Models\BotSession::query()->update(['updated_at' => now()->subHours(7)]);
        $n = count($this->enviados());

        // Al dia siguiente toca el anuncio otra vez: NO es la respuesta a la pregunta vieja.
        $this->meta($this->texto('👋 Hola, quiero mi tienda virtual y deseo más información.'));
        $env = $this->nuevos($n);
        $this->assertSame(['image', 'button:btn:precios|btn:tienda|btn:asesor'], array_map(fn ($d) => $this->resumen($d), $env), 'Empieza de nuevo, no pregunta por las fotos');
    }

    public function test_ver_precios_da_los_tres_planes_directo(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->toque('btn:precios'));
        $env = $this->nuevos($n);

        $this->assertCount(1, $env, 'El flyer ya fue la apertura: solo el texto con botones');
        $this->assertStringContainsString('Start — S/ 490', $this->cuerpo($env[0]));
        $this->assertStringContainsString('Business — S/ 690', $this->cuerpo($env[0]));
        $this->assertSame('button:btn:comparar|btn:tienda|btn:quiero', $this->resumen($env[0]));
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

        $n = count($this->enviados());
        $this->meta($this->texto('Boutique Rosa, @boutiquerosa'));
        $this->assertCount(1, CrmTrato::where('project_id', $this->proyecto->id)->get(), 'No se duplica');
        $this->assertStringContainsString('Anotado', $this->cuerpo($this->nuevos($n)[0]));
        $this->sinIa();

        // Desde aqui atiende una persona: el bot queda en pausa. Un "hola" posterior NO reinicia
        // el embudo (le paso a un cliente real a las 5 am): avisa una vez y calla.
        $conv = WaConversacion::where('cliente_telefono', '51900000001')->firstOrFail();
        $this->assertFalse((bool) $conv->bot_activo, 'El flujo pauso el bot al entregar al asesor');
        $n = count($this->enviados());
        $this->meta($this->texto('Hola'));
        $env = $this->nuevos($n);
        $this->assertCount(1, $env);
        $this->assertStringContainsString('asesor te responde', $this->cuerpo($env[0]));
        $n = count($this->enviados());
        $this->meta($this->texto('No tengo Instagram'));
        $this->assertCount(0, $this->nuevos($n), 'Segundo mensaje en pausa: silencio, ya aviso (y nada de IA saludando)');
        $this->sinIa();
    }

    public function test_el_texto_del_anuncio_entra_por_el_menu_y_no_salta_al_cierre(): void
    {
        $this->meta($this->texto('👋 Hola, quiero mi tienda virtual y deseo más información.'));
        $env = $this->enviados();

        $this->assertCount(2, $env);
        $this->assertSame('button:btn:precios|btn:tienda|btn:asesor', $this->resumen($env[1]), 'Menu comercial, no "¿que tipo de negocio tienes?"');
    }

    public function test_una_rafaga_de_fotos_recibe_una_sola_respuesta_y_todas_quedan_en_el_crm(): void
    {
        $this->meta($this->texto('hola'));
        // Un Http::fake nuevo vacia lo grabado: contar despues de registrarlo.
        Http::fake([
            'graph.facebook.com/*/media*' => Http::response(['url' => 'https://lookaside.fbsbx.com/x', 'mime_type' => 'image/jpeg'], 200),
            'lookaside.fbsbx.com/*'       => Http::response('IMG', 200, ['Content-Type' => 'image/jpeg']),
            'graph.facebook.com/*'        => Http::response(['messages' => [['id' => 'x']]], 200),
        ]);
        $n = count($this->enviados());
        foreach ([1, 2, 3] as $i) {
            $this->meta(['type' => 'image', 'image' => ['id' => 'media' . $i, 'mime_type' => 'image/jpeg']]);
        }
        $textos = array_filter($this->nuevos($n), fn ($d) => $d['type'] === 'text');
        $this->assertCount(1, $textos, 'Tres fotos seguidas: un solo "recibido"');
        $this->assertStringContainsString('Recibí tu imagen', $this->cuerpo(array_values($textos)[0]));
        $this->assertSame(3, WaMensaje::where('direccion', 'in')->where('contenido', '!=', 'hola')->count(), 'Las tres fotos quedan para el asesor');
    }

    public function test_bot_apagado_desde_la_bandeja_no_contesta_pero_guarda(): void
    {
        $this->meta($this->texto('hola'));
        $conv = WaConversacion::where('cliente_telefono', '51900000001')->firstOrFail();
        $conv->update(['bot_activo' => false]);
        $n = count($this->enviados());

        $this->meta($this->texto('precio?'));
        $env = $this->nuevos($n);
        $this->assertCount(1, $env, 'Solo el aviso de que atiende una persona');
        $this->assertStringNotContainsString('S/ 490', $this->cuerpo($env[0]));
        $this->assertDatabaseHas('wa_mensajes', ['wa_conversacion_id' => $conv->id, 'direccion' => 'in', 'contenido' => 'precio?']);
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
        $this->assertCount(2, $env, 'Flyer de presentacion + precios; sin el menu en medio');
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
            $this->assertContains($esperado, array_map(fn ($d) => $this->resumen($d), $env), $msg . ' (tras el flyer de apertura)');
        }
        $this->sinIa();
    }

    public function test_texto_que_no_se_entiende_vuelve_al_menu(): void
    {
        $this->meta($this->texto('hola'));
        $n = count($this->enviados());
        $this->meta($this->texto('asdfgh'));
        $env = $this->nuevos($n);
        $this->assertCount(1, $env, 'Vuelve al menu sin repetir el flyer');
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
    public function test_una_duda_en_medio_de_la_pregunta_se_atiende_y_se_retoma_la_pregunta(): void
    {
        $this->meta($this->texto('hola'));
        $this->meta($this->toque('btn:quiero'));
        $n = count($this->enviados());
        // Pregunta por el dominio en vez de responder que negocio tiene.
        $this->meta($this->texto('ya tengo un dominio, lo puedo usar? es de cpanel'));
        $env = $this->nuevos($n);

        $this->assertGreaterThanOrEqual(2, count($env));
        $this->assertStringContainsString('asesor te la responde', $this->cuerpo($env[0]), 'Sin IA licenciada, la duda va al asesor (no se ignora)');
        $this->assertStringContainsString('fotos listas', $this->cuerpo(end($env)), 'Y se retoma la pregunta pendiente');

        // Ahora si responde: sigue el cierre.
        $n = count($this->enviados());
        $this->meta($this->texto('sí, tengo todo listo'));
        $env = $this->nuevos($n);
        $this->assertStringContainsString('nombre de tu negocio', $this->cuerpo($env[0]));
    }
}
