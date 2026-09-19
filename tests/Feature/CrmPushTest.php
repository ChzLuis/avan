<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Models\BotFlow;
use App\Modules\Crm\Models\PushSuscripcion;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Support\WebPush\PushWeb;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notificaciones push del CRM: claves VAPID propias, cifrado RFC 8291 que el
 * navegador puede abrir, suscripcion por usuario/negocio y aviso al entrar un mensaje.
 */
class CrmPushTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM push', 'slug' => 'crm-push-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    /** Par de claves del "navegador" (P-256) en el formato que manda PushManager. */
    private function navegador(): array
    {
        $key = openssl_pkey_new(PushWeb::opcionesEc());
        $d = openssl_pkey_get_details($key)['ec'];
        $punto = "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
        $auth = random_bytes(16);

        return [$key, $punto, $auth, PushWeb::b64url($punto), PushWeb::b64url($auth)];
    }

    /** Descifra como lo haria el navegador (RFC 8291) para comprobar que el cifrado es correcto. */
    private function descifrar($uaKey, string $uaPunto, string $auth, string $cuerpo): string
    {
        $salt = substr($cuerpo, 0, 16);
        $idlen = ord($cuerpo[20]);
        $asPunto = substr($cuerpo, 21, $idlen);
        $cifrado = substr($cuerpo, 21 + $idlen);
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $asPunto;
        $asPub = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n");
        $ecdh = openssl_pkey_derive($asPub, $uaKey, 32);
        $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0" . $uaPunto . $asPunto, $auth);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
        $tag = substr($cifrado, -16);
        $texto = openssl_decrypt(substr($cifrado, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        $this->assertNotFalse($texto, 'El navegador no pudo descifrar');

        return rtrim($texto, "\x02");
    }

    public function test_las_claves_vapid_se_generan_una_vez_y_la_publica_es_un_punto_p256(): void
    {
        $a = PushWeb::clavePublica();
        $b = PushWeb::clavePublica();
        $this->assertSame($a, $b, 'La clave se crea una sola vez');
        $this->assertSame(65, strlen(PushWeb::b64urlDecode($a)));
        $this->assertSame("\x04", PushWeb::b64urlDecode($a)[0]);
        $this->assertSame(1, \DB::table('push_claves')->count());
    }

    public function test_el_cifrado_aes128gcm_lo_puede_abrir_el_navegador(): void
    {
        [$uaKey, $uaPunto, $auth, $p256dh, $authB64] = $this->navegador();
        $cuerpo = PushWeb::cifrar($p256dh, $authB64, '{"titulo":"Rosa","cuerpo":"hola ¿precio?"}');

        $this->assertSame(4096, unpack('N', substr($cuerpo, 16, 4))[1], 'Tamano de registro en la cabecera');
        $this->assertSame('{"titulo":"Rosa","cuerpo":"hola ¿precio?"}', $this->descifrar($uaKey, $uaPunto, $auth, $cuerpo));
    }

    public function test_la_cabecera_vapid_lleva_un_jwt_es256_firmado_para_el_origen_del_endpoint(): void
    {
        $vapid = PushWeb::vapid('https://fcm.googleapis.com/fcm/send/abc');
        $this->assertMatchesRegularExpression('/^vapid t=[\w-]+\.[\w-]+\.[\w-]+, k=[\w-]+$/', $vapid);
        preg_match('/t=([^,]+), k=(.+)$/', $vapid, $m);
        [$cab, $cla, $firma] = explode('.', $m[1]);
        $this->assertSame(['typ' => 'JWT', 'alg' => 'ES256'], json_decode(PushWeb::b64urlDecode($cab), true));
        $claims = json_decode(PushWeb::b64urlDecode($cla), true);
        $this->assertSame('https://fcm.googleapis.com', $claims['aud']);
        $this->assertSame(64, strlen(PushWeb::b64urlDecode($firma)), 'Firma r||s de 64 bytes');
        $this->assertSame(PushWeb::clavePublica(), $m[2]);
    }

    public function test_suscribirse_guarda_el_dispositivo_para_el_usuario_y_el_negocio_activo(): void
    {
        $this->enElCrm()->getJson('/bixocrm/push/clave')->assertOk()->assertJsonStructure(['clave']);
        $r = $this->enElCrm()->postJson('/bixocrm/push/suscribir', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz', 'keys' => ['p256dh' => 'P', 'auth' => 'A']])->assertOk();
        $this->assertTrue($r->json('ok'));
        $this->assertDatabaseHas('push_suscripciones', ['user_id' => $this->usuario->id, 'project_id' => $this->proyecto->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz']);

        // Reenviar la misma suscripcion no duplica.
        $this->enElCrm()->postJson('/bixocrm/push/suscribir', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz', 'keys' => ['p256dh' => 'P2', 'auth' => 'A2']])->assertOk();
        $this->assertSame(1, PushSuscripcion::count());
        $this->assertSame('P2', PushSuscripcion::first()->p256dh);

        $this->enElCrm()->deleteJson('/bixocrm/push/suscribir', ['endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz'])->assertOk();
        $this->assertSame(0, PushSuscripcion::count());
    }

    public function test_un_mensaje_entrante_por_meta_dispara_el_push_a_los_dispositivos_del_negocio(): void
    {
        [$uaKey, $uaPunto, $auth, $p256dh, $authB64] = $this->navegador();
        PushSuscripcion::create(['user_id' => $this->usuario->id, 'project_id' => $this->proyecto->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/dev1', 'p256dh' => $p256dh, 'auth' => $authB64]);
        // Otro negocio con su propio dispositivo: no debe recibir nada.
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        PushSuscripcion::create(['user_id' => $otro->owner_id, 'project_id' => $otro->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/ajeno', 'p256dh' => $p256dh, 'auth' => $authB64]);

        WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'phone_number_id' => '555', 'access_token' => 't', 'app_secret' => 'sec', 'verify_token' => 'v', 'activo' => true]);
        BotFlow::comercialDe($this->proyecto)->update(['activo' => true, 'definicion' => ['disparos' => [], 'inicio' => 'a', 'bloques' => ['a' => ['tipo' => 'mensaje', 'texto' => 'Hola']]]]);
        Http::fake([
            'graph.facebook.com/*'  => Http::response(['messages' => [['id' => 'x']]], 200),
            'fcm.googleapis.com/*'  => Http::response('', 201),
        ]);
        $cuerpo = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '555'],
            'contacts' => [['profile' => ['name' => 'Rosa'], 'wa_id' => '51900000009']],
            'messages' => [['from' => '51900000009', 'id' => 'wamid.' . uniqid(), 'type' => 'text', 'text' => ['body' => 'hola, precio?']]],
        ]]]]]]);
        $this->call('POST', '/api/whatsapp/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $cuerpo, 'sec')], $cuerpo)->assertOk();
        // El aviso sale tras responder el webhook: en pruebas se ejecuta al terminar la peticion.
        $this->app->terminate();

        Http::assertSent(function ($req) use ($uaKey, $uaPunto, $auth) {
            if (! str_contains($req->url(), 'fcm.googleapis.com/fcm/send/dev1')) return false;
            $this->assertStringStartsWith('vapid t=', $req->header('Authorization')[0]);
            $this->assertSame('aes128gcm', $req->header('Content-Encoding')[0]);
            $d = json_decode($this->descifrar($uaKey, $uaPunto, $auth, $req->body()), true);
            return $d['titulo'] === 'Rosa' && $d['cuerpo'] === 'hola, precio?' && str_contains($d['url'], '/bixocrm?conversacion=');
        });
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'fcm/send/ajeno'));
    }

    public function test_una_suscripcion_caducada_410_se_borra_sola(): void
    {
        [, , , $p256dh, $authB64] = $this->navegador();
        PushSuscripcion::create(['user_id' => $this->usuario->id, 'project_id' => $this->proyecto->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/viejo', 'p256dh' => $p256dh, 'auth' => $authB64]);
        Http::fake(['fcm.googleapis.com/*' => Http::response('', 410)]);

        $r = $this->enElCrm()->postJson('/bixocrm/push/probar')->assertOk();

        $this->assertFalse($r->json('ok'));
        $this->assertSame(0, PushSuscripcion::count());
    }
}
