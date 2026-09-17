<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WaCanal;
use App\Models\WaConversacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El webhook publico de WhatsApp solo atiende a Meta.
 *
 * `/whatsapp/webhook` y `/wa/webhook/{slug}` son URLs publicas: no llevan
 * sesion ni token de API. Sin comprobar la firma HMAC del cuerpo, cualquiera
 * que conociera la direccion podia mandar un `phone_number_id` real y hacer
 * que el bot contestara, cotizara o registrara leads en nombre de un cliente.
 *
 * Estas pruebas fijan ese limite y, a la vez, que una linea configurada ANTES
 * de que existiera la firma (sin `app_secret`) siga funcionando: cerrar el
 * agujero no puede dejar mudas a las que ya estaban en produccion.
 */
class WaWebhookFirmaTest extends TestCase
{
    use RefreshDatabase;

    private function canal(?string $appSecret, string $phoneNumberId = '555111'): WaCanal
    {
        $proyecto = Project::create([
            'name'     => 'Negocio ' . $phoneNumberId,
            'slug'     => 'negocio-' . uniqid(),
            'owner_id' => User::factory()->create()->id,
        ]);

        return WaCanal::create([
            'project_id'      => $proyecto->id,
            'nombre'          => 'Línea WhatsApp',
            'tipo'            => 'bixo',
            'phone_number_id' => $phoneNumberId,
            'access_token'    => 'token-meta',
            'app_secret'      => $appSecret,
            'verify_token'    => 'verif-' . $phoneNumberId,
            'activo'          => true,
        ]);
    }

    private function evento(string $phoneNumberId): string
    {
        return json_encode([
            'object' => 'whatsapp_business_account',
            'entry'  => [[
                'changes' => [['field' => 'messages', 'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => $phoneNumberId],
                    'contacts' => [['profile' => ['name' => 'Intruso'], 'wa_id' => '51900000009']],
                    'messages' => [[
                        'from' => '51900000009',
                        'id'   => 'wamid.' . uniqid(),
                        'type' => 'text',
                        'text' => ['body' => 'hola'],
                    ]],
                ]]],
            ]],
        ]);
    }

    private function golpear(string $url, string $cuerpo, ?string $secreto)
    {
        $cabeceras = ['Content-Type' => 'application/json'];
        if ($secreto !== null) {
            $cabeceras['X-Hub-Signature-256'] = 'sha256=' . hash_hmac('sha256', $cuerpo, $secreto);
        }

        return $this->call('POST', $url, [], [], [], $this->transformHeadersToServerVars($cabeceras), $cuerpo);
    }

    /** Un tercero sin la firma NO puede abrir una conversacion en el CRM. */
    public function test_sin_firma_no_entra_al_crm(): void
    {
        $this->canal('secreto-real');
        Http::fake();

        $this->golpear('/whatsapp/webhook', $this->evento('555111'), null)->assertOk();

        $this->assertSame(0, WaConversacion::count(), 'Un mensaje sin firmar llegó al CRM.');
    }

    /** Firmar con OTRO secreto tampoco sirve. */
    public function test_firma_equivocada_no_entra_al_crm(): void
    {
        $this->canal('secreto-real');
        Http::fake();

        $this->golpear('/whatsapp/webhook', $this->evento('555111'), 'secreto-inventado')->assertOk();

        $this->assertSame(0, WaConversacion::count(), 'Una firma inválida llegó al CRM.');
    }

    /** Con la firma correcta el mensaje sí se procesa. */
    public function test_con_la_firma_correcta_se_procesa(): void
    {
        $this->canal('secreto-real');
        Http::fake();

        $this->golpear('/whatsapp/webhook', $this->evento('555111'), 'secreto-real')->assertOk();

        $this->assertSame(1, WaConversacion::count(), 'El mensaje legítimo no se procesó.');
    }

    /**
     * Compatibilidad: una linea dada de alta antes de que existiera la firma
     * (sin app_secret) NO puede quedarse muda al desplegar este cambio.
     */
    public function test_un_canal_sin_secreto_sigue_funcionando(): void
    {
        $this->canal(null, '555222');
        Http::fake();

        $this->golpear('/whatsapp/webhook', $this->evento('555222'), null)->assertOk();

        $this->assertSame(1, WaConversacion::count(), 'Una línea heredada quedó muda.');
    }

    /** La ruta por slug tambien exige firma cuando el canal tiene secreto. */
    public function test_la_ruta_por_slug_tambien_exige_firma(): void
    {
        $canal = $this->canal('secreto-real', '555333');
        Http::fake();

        $this->golpear("/wa/webhook/{$canal->verify_token}", $this->evento('555333'), null)->assertOk();

        $this->assertSame(0, WaConversacion::count(), 'La ruta por slug aceptó un mensaje sin firmar.');
    }

    /** El handshake acepta el verify_token propio de cada canal. */
    public function test_el_handshake_acepta_el_token_del_canal(): void
    {
        $canal = $this->canal('secreto-real', '555444');

        $this->get("/whatsapp/webhook?hub_mode=subscribe&hub_verify_token={$canal->verify_token}&hub_challenge=RETO")
            ->assertOk()
            ->assertSee('RETO');
    }

    public function test_el_handshake_rechaza_un_token_inventado(): void
    {
        $this->canal('secreto-real', '555555');

        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=inventado&hub_challenge=RETO')
            ->assertForbidden();
    }
}
