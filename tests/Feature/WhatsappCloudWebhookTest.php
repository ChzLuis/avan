<?php

namespace Tests\Feature;

use App\Modules\Bots\Models\BotFlow;
use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
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
}
