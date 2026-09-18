<?php

namespace Tests\Feature;

use App\Modules\Bots\Models\BotFlow;
use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Webhook de la WhatsApp Cloud API (Meta).
 *
 * Lo que se vigila aqui es que un tercero NO pueda hacer hablar al bot y que
 * el mensaje de un negocio no acabe atendido por otro.
 */
class WhatsappCloudWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function negocio(string $nombre, string $phoneNumberId, string $secreto, string $respuesta): array
    {
        $dueno = User::factory()->create();
        $proyecto = Project::create([
            'name'     => $nombre,
            'slug'     => \Illuminate\Support\Str::slug($nombre) . '-' . uniqid(),
            'owner_id' => $dueno->id,
        ]);

        $canal = WaCanal::create([
            'project_id'      => $proyecto->id,
            'nombre'          => 'Línea de ' . $nombre,
            'tipo'            => 'bixo',
            'phone_number_id' => $phoneNumberId,
            'access_token'    => 'token-' . $phoneNumberId,
            'app_secret'      => $secreto,
            'verify_token'    => 'verif-' . $phoneNumberId,
            'activo'          => true,
        ]);

        BotFlow::create([
            'project_id' => $proyecto->id,
            'nombre'     => 'Bot de ' . $nombre,
            'activo'     => true,
            'definicion' => [
                'disparos' => [],
                'inicio'   => 'saludo',
                'bloques'  => ['saludo' => ['tipo' => 'mensaje', 'texto' => $respuesta]],
            ],
        ]);

        return [$proyecto, $canal];
    }

    private function evento(string $phoneNumberId, string $texto = 'hola'): string
    {
        return json_encode(['object' => 'whatsapp_business_account', 'entry' => [[
            'changes' => [['field' => 'messages', 'value' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['phone_number_id' => $phoneNumberId],
                'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
                'messages' => [[
                    'from' => '51900000001',
                    'id'   => 'wamid.' . uniqid(),
                    'type' => 'text',
                    'text' => ['body' => $texto],
                ]],
            ]]],
        ]]]);
    }

    private function enviar(string $cuerpo, ?string $secreto): \Illuminate\Testing\TestResponse
    {
        $cabeceras = ['Content-Type' => 'application/json'];
        if ($secreto !== null) {
            $cabeceras['X-Hub-Signature-256'] = 'sha256=' . hash_hmac('sha256', $cuerpo, $secreto);
        }

        return $this->call('POST', '/api/whatsapp/webhook', [], [], [], $this->transformHeadersToServerVars($cabeceras), $cuerpo);
    }

    public function test_sin_firma_no_responde(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111'), null)->assertOk();

        Http::assertNothingSent();
    }

    public function test_firma_invalida_no_responde(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111'), 'secreto-equivocado')->assertOk();

        Http::assertNothingSent();
    }

    public function test_firma_valida_responde_por_la_graph_api(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111'), 'secreto-a')->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['text']['body'] ?? '') === 'Hola desde A');

        // La conversacion queda en la LINEA DE META por la que entro, no en el
        // canal 'Bot WhatsApp' de Baileys: si no, la bandeja no podia responder
        // ("este canal aun no esta conectado"). Visto en produccion 2026-09-18.
        $conv = \App\Modules\Crm\Models\WaConversacion::firstOrFail();
        $this->assertSame('111', $conv->canal->phone_number_id, 'La conversacion no quedo en la linea de Meta.');
        $this->assertSame(0, \App\Modules\Crm\Models\WaCanal::where('tipo', 'bot')->count(), 'No debe nacer un canal Bot de Baileys al entrar por Meta.');
    }

    /** Un negocio no puede hacer hablar al bot de otro con SU propia firma. */
    public function test_la_firma_de_un_negocio_no_sirve_para_la_linea_de_otro(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        $this->negocio('Negocio B', '222', 'secreto-b', 'Hola desde B');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('222'), 'secreto-a')->assertOk();

        Http::assertNothingSent();
    }

    /** Cada linea la atiende SU bot: un cruce seria una fuga entre inquilinos. */
    public function test_cada_linea_la_atiende_su_propio_bot(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        $this->negocio('Negocio B', '222', 'secreto-b', 'Hola desde B');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('222'), 'secreto-b')->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['text']['body'] ?? '') === 'Hola desde B');
        Http::assertNotSent(fn ($req) => ($req->data()['text']['body'] ?? '') === 'Hola desde A');
    }

    public function test_linea_desactivada_no_responde(): void
    {
        [, $canal] = $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        $canal->forceFill(['activo' => false])->saveQuietly();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111'), 'secreto-a')->assertOk();

        Http::assertNothingSent();
    }

    /** Meta reentrega el mismo evento si tarda la respuesta: no debe duplicar. */
    public function test_el_mismo_mensaje_no_se_responde_dos_veces(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $cuerpo = $this->evento('111');
        $this->enviar($cuerpo, 'secreto-a')->assertOk();
        $this->enviar($cuerpo, 'secreto-a')->assertOk();

        $respuestas = collect(Http::recorded())
            ->filter(fn ($p) => ($p[0]->data()['type'] ?? '') === 'text')
            ->count();
        $this->assertSame(1, $respuestas, 'La reentrega de Meta duplicó la respuesta.');
    }

    public function test_handshake_devuelve_el_challenge_con_el_token_correcto(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');

        $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=verif-111&hub_challenge=ABC123')
            ->assertOk()
            ->assertSee('ABC123');
    }

    public function test_handshake_rechaza_un_token_equivocado(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');

        $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=otro&hub_challenge=ABC123')
            ->assertForbidden();
    }

    /** Un sticker es un mensaje deliberado: el bot no puede quedarse mudo. */
    public function test_un_sticker_recibe_respuesta(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $cuerpo = json_encode(['entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => '111'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
            'messages' => [['from' => '51900000001', 'id' => 'wamid.' . uniqid(),
                            'type' => 'sticker', 'sticker' => ['id' => 's1']]],
        ]]]]]]);

        $this->enviar($cuerpo, 'secreto-a')->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['type'] ?? '') === 'text');
    }

    /** Una reaccion (👍 sobre un mensaje viejo) no debe abrir conversacion. */
    public function test_una_reaccion_no_dispara_al_bot(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $cuerpo = json_encode(['entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => '111'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
            'messages' => [['from' => '51900000001', 'id' => 'wamid.' . uniqid(),
                            'type' => 'reaction', 'reaction' => ['emoji' => '👍', 'message_id' => 'w1']]],
        ]]]]]]);

        $this->enviar($cuerpo, 'secreto-a')->assertOk();

        Http::assertNothingSent();
    }

    /** El cuerpo se lee crudo: sin Content-Type el evento tambien se atiende. */
    /** La foto que manda el cliente se descarga al disco del negocio y queda en el historial. */
    public function test_una_imagen_del_cliente_se_descarga_y_queda_en_la_conversacion(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Recibido');
        \Illuminate\Support\Facades\Storage::fake('public');
        Http::fake([
            'graph.facebook.com/*/MEDIA1' => Http::response(['url' => 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=1', 'mime_type' => 'image/jpeg'], 200),
            'lookaside.fbsbx.com/*'       => Http::response('FOTO', 200, ['Content-Type' => 'image/jpeg']),
            'graph.facebook.com/*'        => Http::response(['messages' => [['id' => 'x']]], 200),
        ]);
        $cuerpo = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '111'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
            'messages' => [['from' => '51900000001', 'id' => 'wamid.' . uniqid(), 'type' => 'image', 'image' => ['id' => 'MEDIA1', 'mime_type' => 'image/jpeg', 'caption' => 'mi foto']]],
        ]]]]]]);

        $this->enviar($cuerpo, 'secreto-a')->assertOk();

        $m = \App\Modules\Crm\Models\WaMensaje::where('direccion', 'in')->firstOrFail();
        $this->assertSame('imagen', $m->tipo);
        $this->assertSame('mi foto', $m->contenido);
        $this->assertStringContainsString('/storage/wa/', $m->media_url);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('wa/' . $m->conversacion->canal->project_id . '/in/MEDIA1.jpg');
    }

    /** Con 3 opciones o menos, por Meta salen BOTONES nativos; el id del boton es el numero de la opcion. */
    public function test_las_opciones_salen_como_botones_nativos_por_meta(): void
    {
        [$proyecto] = $this->negocio('Negocio A', '111', 'secreto-a', 'ignorado');
        BotFlow::where('project_id', $proyecto->id)->update(['definicion' => json_encode([
            'disparos' => [], 'inicio' => 'menu',
            'bloques'  => [
                'menu'    => ['tipo' => 'opciones', 'texto' => '¿Qué necesitas?', 'opciones' => [
                    ['texto' => 'Ver planes', 'siguiente' => 'planes'], ['texto' => 'Hablar con un asesor', 'siguiente' => 'asesor'],
                ]],
                'planes'  => ['tipo' => 'mensaje', 'texto' => 'Desde S/ 490'],
                'asesor'  => ['tipo' => 'mensaje', 'texto' => 'Te atiende una persona'],
            ],
        ])]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111', 'hola'), 'secreto-a')->assertOk();

        Http::assertSent(function ($req) {
            $d = $req->data();
            return ($d['type'] ?? '') === 'interactive'
                && $d['interactive']['type'] === 'button'
                && $d['interactive']['body']['text'] === '¿Qué necesitas?'
                && $d['interactive']['action']['buttons'][0]['reply'] === ['id' => '1', 'title' => 'Ver planes']
                && $d['interactive']['action']['buttons'][1]['reply']['title'] === 'Hablar con un asesor';
        });
        // Marcar leido lleva el "escribiendo..." de Meta.
        Http::assertSent(fn ($req) => ($req->data()['status'] ?? '') === 'read' && ($req->data()['typing_indicator']['type'] ?? '') === 'text');
    }

    /** El cliente toca el boton: llega button_reply con id "2" y el motor sigue por esa rama. */
    public function test_tocar_un_boton_sigue_por_su_rama(): void
    {
        [$proyecto] = $this->negocio('Negocio A', '111', 'secreto-a', 'ignorado');
        BotFlow::where('project_id', $proyecto->id)->update(['definicion' => json_encode([
            'disparos' => [], 'inicio' => 'menu',
            'bloques'  => [
                'menu'   => ['tipo' => 'opciones', 'texto' => '¿Qué necesitas?', 'opciones' => [['texto' => 'Planes', 'siguiente' => 'planes'], ['texto' => 'Asesor', 'siguiente' => 'asesor']]],
                'planes' => ['tipo' => 'mensaje', 'texto' => 'Desde S/ 490'],
                'asesor' => ['tipo' => 'mensaje', 'texto' => 'Te atiende una persona'],
            ],
        ])]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $this->enviar($this->evento('111', 'hola'), 'secreto-a')->assertOk();

        $toque = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '111'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
            'messages' => [['from' => '51900000001', 'id' => 'wamid.' . uniqid(), 'type' => 'interactive',
                'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => '2', 'title' => 'Asesor']]]],
        ]]]]]]);
        $this->enviar($toque, 'secreto-a')->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['text']['body'] ?? '') === 'Te atiende una persona');
    }

    /** Un bloque `intencion` con `botones` y `enlace` sale como cta_url + botones; tocar uno salta a su bloque sin IA. */
    public function test_intencion_con_botones_y_enlace_usa_lo_nativo_de_meta(): void
    {
        [$proyecto] = $this->negocio('Negocio A', '111', 'secreto-a', 'ignorado');
        BotFlow::where('project_id', $proyecto->id)->update(['definicion' => json_encode([
            'disparos' => [], 'inicio' => 'demo',
            'bloques'  => [
                'demo'   => ['tipo' => 'intencion', 'esperar' => true, 'texto' => 'Mira la demo', 'confirmacion' => 'asesor', 'negacion' => 'luego',
                    'enlace' => ['url' => 'https://arindg.com/ferreteria-demo', 'boton' => 'Abrir la demo'],
                    'botones' => [['titulo' => 'Sí, quiero', 'siguiente' => 'asesor'], ['titulo' => 'Ahora no', 'siguiente' => 'luego'], ['titulo' => 'Roto', 'siguiente' => 'no_existe']]],
                'asesor' => ['tipo' => 'mensaje', 'texto' => 'Te atiende una persona'],
                'luego'  => ['tipo' => 'mensaje', 'texto' => 'Cuando quieras'],
            ],
        ])]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111', 'hola'), 'secreto-a')->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['interactive']['type'] ?? '') === 'cta_url'
            && $req->data()['interactive']['action']['parameters']['url'] === 'https://arindg.com/ferreteria-demo'
            && $req->data()['interactive']['body']['text'] === 'Mira la demo');
        Http::assertSent(function ($req) {
            $b = $req->data()['interactive']['action']['buttons'] ?? null;
            return ($req->data()['interactive']['type'] ?? '') === 'button' && count($b) === 2
                && $b[0]['reply'] === ['id' => 'btn:asesor', 'title' => 'Sí, quiero'];
        });

        $toque = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '111'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
            'messages' => [['from' => '51900000001', 'id' => 'wamid.' . uniqid(), 'type' => 'interactive',
                'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => 'btn:luego', 'title' => 'Ahora no']]]],
        ]]]]]]);
        $this->enviar($toque, 'secreto-a')->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['text']['body'] ?? '') === 'Cuando quieras');
    }

    public function test_atiende_aunque_falte_el_content_type(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $cuerpo = $this->evento('111');
        $this->call('POST', '/api/whatsapp/webhook', [], [], [], $this->transformHeadersToServerVars([
            'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $cuerpo, 'secreto-a'),
        ]), $cuerpo)->assertOk();

        Http::assertSent(fn ($req) => ($req->data()['text']['body'] ?? '') === 'Hola desde A');
    }

    /**
     * Un anuncio click-to-WhatsApp adjunta `referral` al primer mensaje. Es el
     * unico momento en que Meta dice que anuncio pago esa conversacion: si no
     * se guarda ahi, el gasto de la campaña queda sin atribuir para siempre.
     */
    public function test_un_mensaje_desde_un_anuncio_guarda_la_atribucion(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $cuerpo = json_encode(['entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => '111'],
            'contacts' => [['profile' => ['name' => 'Cliente'], 'wa_id' => '51900000001']],
            'messages' => [[
                'from' => '51900000001',
                'id'   => 'wamid.' . uniqid(),
                'type' => 'text',
                'text' => ['body' => 'hola, vi su anuncio'],
                'referral' => [
                    'source_url'  => 'https://fb.me/xyz',
                    'source_id'   => '120246997153260068',
                    'source_type' => 'ad',
                    'headline'    => 'Tu tienda virtual desde S/490',
                    'media_type'  => 'image',
                    'ctwa_clid'   => 'ARBcdef123',
                ],
            ]],
        ]]]]]]);

        $this->enviar($cuerpo, 'secreto-a')->assertOk();

        $conv = WaConversacion::where('cliente_telefono', '51900000001')->first();

        $this->assertNotNull($conv, 'La conversación no llegó al CRM.');
        $this->assertSame('120246997153260068', $conv->anuncio_id);
        $this->assertSame('anuncio_meta', $conv->origen_anuncio);
        $this->assertSame('ARBcdef123', $conv->anuncio_referral['ctwa_clid'] ?? null);
    }

    /** Sin anuncio no se inventa atribucion: lo organico queda como organico. */
    public function test_un_mensaje_organico_no_guarda_atribucion(): void
    {
        $this->negocio('Negocio A', '111', 'secreto-a', 'Hola desde A');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $this->enviar($this->evento('111'), 'secreto-a')->assertOk();

        $conv = WaConversacion::where('cliente_telefono', '51900000001')->first();

        $this->assertNotNull($conv, 'La conversación no llegó al CRM.');
        $this->assertNull($conv->anuncio_id);
        $this->assertNull($conv->anuncio_referral);
    }
}
