<?php

namespace Tests\Feature;

use App\Models\BotFlow;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El webhook /api/bot/inbound: el camino EXACTO por el que llegan los mensajes
 * de WhatsApp. Protege el filtro de disparos y su excepción: un cliente que ya
 * conversó con el bot nunca se queda sin respuesta.
 */
class BotWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Tienda Webhook',
            'slug'      => 'tienda-webhook',
            'is_active' => true,
        ]);
        // El observer ya le dio token y bot; solo hay que encenderlo.
        BotFlow::comercialDe($this->project)->update(['activo' => true]);
    }

    private function manda(string $telefono, string $mensaje)
    {
        return $this->postJson('/api/bot/inbound', [
            'telefono' => $telefono,
            'mensaje'  => $mensaje,
            'nombre'   => 'Cliente Test',
        ], ['X-Copilot-Token' => $this->project->fresh()->copilot_token]);
    }

    /** Primer contacto sin palabra de disparo: silencio (podría ser un chat privado). */
    public function test_primer_contacto_sin_disparo_no_responde(): void
    {
        $r = $this->manda('51911111111', 'nos vemos a las 8 en la casa de mi mama');

        $r->assertOk()->assertJson(['sin_disparo' => true]);
        $this->assertSame([], $r->json('respuestas'));
    }

    /** Primer contacto con disparo comercial: el bot atiende. */
    public function test_primer_contacto_con_disparo_responde(): void
    {
        $r = $this->manda('51922222222', 'hola');

        $r->assertOk();
        $this->assertNotEmpty($r->json('respuestas'));
        $this->assertStringContainsString('Tienda Webhook', json_encode($r->json('respuestas'), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Un cliente que YA conversó no vuelve a pasar por el filtro: si su flujo
     * murió y responde "3" a un menú viejo, el bot retoma en vez de callarse
     * (quedó mudo en la prueba real de producción).
     */
    public function test_cliente_conocido_recibe_respuesta_aunque_no_haya_disparo(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Cocina a gas', 'price' => 490]);

        // Conversación completa que termina (el estado del flujo muere).
        $this->manda('51933333333', 'hola');
        $this->manda('51933333333', 'gracias');

        // "3" no coincide con ningún disparo, pero el cliente es conocido.
        $r = $this->manda('51933333333', '3');

        $r->assertOk();
        $this->assertNotEmpty($r->json('respuestas'), 'Un cliente conocido nunca se queda sin respuesta.');
        $this->assertStringNotContainsString('No encontré "3"', json_encode($r->json('respuestas'), JSON_UNESCAPED_UNICODE));
    }

    /** El panel muestra el QR que reporta el conector de ESTA empresa, y solo si es reciente. */
    public function test_el_panel_lee_el_qr_del_conector_de_su_empresa(): void
    {
        $dir = storage_path('app/bot-wa');
        if (! is_dir($dir)) mkdir($dir, 0775, true);
        $file = "{$dir}/{$this->project->id}.json";

        $user = $this->project->owner;
        $yo = fn () => $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);

        try {
            // Sin reporte: sin conexion.
            @unlink($file);
            $yo()->getJson(route('bot-flows.wa-status'))->assertOk()->assertJson(['status' => 'offline', 'qr' => null]);

            // Reporte reciente con QR: se muestra.
            file_put_contents($file, json_encode(['status' => 'qr', 'qr' => 'data:image/png;base64,QR', 'ts' => time()]));
            $yo()->getJson(route('bot-flows.wa-status'))->assertOk()->assertJson(['status' => 'qr', 'qr' => 'data:image/png;base64,QR']);

            // Reporte viejo (>60 s): el conector esta caido, no se ensena un QR muerto.
            file_put_contents($file, json_encode(['status' => 'qr', 'qr' => 'data:image/png;base64,QR', 'ts' => time() - 120]));
            $yo()->getJson(route('bot-flows.wa-status'))->assertOk()->assertJson(['status' => 'offline', 'qr' => null]);
        } finally {
            @unlink($file);
        }
    }

    /**
     * Adjuntos: una foto o un audio NO se ignoran en silencio ni se "buscan"
     * como producto — respuesta honesta que orienta al texto o al asesor.
     */
    public function test_una_imagen_recibe_respuesta_honesta(): void
    {
        $r = $this->postJson('/api/bot/inbound', [
            'telefono' => '51955000001', 'mensaje' => '[📷 Imagen]', 'tipo' => 'imagen', 'nombre' => 'Cliente',
        ], ['X-Copilot-Token' => $this->project->fresh()->copilot_token]);

        $r->assertOk()->assertJson(['adjunto' => 'imagen']);
        $txt = json_encode($r->json('respuestas'), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('imagen', $txt);
        $this->assertStringContainsString('asesor', $txt);
        $this->assertStringNotContainsString('No encontré', $txt);
    }

    public function test_un_audio_pide_el_texto_sin_buscar_nada(): void
    {
        $r = $this->postJson('/api/bot/inbound', [
            'telefono' => '51955000002', 'mensaje' => '[🎧 Audio]', 'tipo' => 'audio',
        ], ['X-Copilot-Token' => $this->project->fresh()->copilot_token]);

        $r->assertOk();
        $txt = json_encode($r->json('respuestas'), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('audio', $txt);
        $this->assertStringNotContainsString('catálogo', $txt);
    }

    /** El adjunto NO rompe el flujo: tras la foto, el cliente sigue donde estaba. */
    public function test_un_adjunto_no_rompe_el_flujo_en_curso(): void
    {
        // Dos SECCIONES afines: la busqueda pregunta cual.
        \App\Models\Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);
        \App\Models\Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas Industriales']);

        $this->manda('51955000003', 'hola');
        $this->manda('51955000003', 'cocina');
        // Manda una foto a mitad de la eleccion...
        $this->postJson('/api/bot/inbound', [
            'telefono' => '51955000003', 'mensaje' => '[📷 Imagen]', 'tipo' => 'imagen',
        ], ['X-Copilot-Token' => $this->project->fresh()->copilot_token])->assertOk();
        // ...y el "1" sigue eligiendo la seccion que tenia delante.
        $r = $this->manda('51955000003', '1');

        $this->assertStringContainsString('/tienda/', json_encode($r->json('respuestas'), JSON_UNESCAPED_SLASHES));
    }

    public function test_token_invalido_da_401(): void
    {
        $this->postJson('/api/bot/inbound', [
            'telefono' => '51944444444', 'mensaje' => 'hola',
        ], ['X-Copilot-Token' => 'token-falso'])->assertStatus(401);
    }
}
