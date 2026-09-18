<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bandeja del CRM: adjuntar imagenes y PDF, y reenviar mensajes a otra
 * conversacion. El adjunto se guarda en el disco publico del negocio y a
 * Meta se le pasa la URL (la Graph API lo descarga desde ahi).
 */
class CrmBandejaMediosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;
    private WaCanal $canal;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // Cada envio recibe un wamid distinto: la columna es unica.
        Http::fake(['graph.facebook.com/*' => fn () => Http::response(['messages' => [['id' => 'wamid.' . uniqid()]]], 200)]);

        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM medios', 'slug' => 'crm-medios-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
        $this->canal = WaCanal::create([
            'project_id' => $this->proyecto->id, 'nombre' => 'Linea', 'tipo' => 'bixo', 'activo' => true,
            'phone_number_id' => '123', 'access_token' => 'TOKEN', 'app_secret' => 'S', 'verify_token' => 'v',
        ]);
    }

    private function conversacion(string $telefono = '51999111222'): WaConversacion
    {
        return WaConversacion::create([
            'wa_canal_id' => $this->canal->id, 'cliente_nombre' => 'Cliente ' . $telefono, 'cliente_telefono' => $telefono,
            'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false,
        ]);
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    public function test_una_imagen_se_guarda_en_el_disco_publico_y_se_envia_por_url(): void
    {
        $conv = $this->conversacion();

        $this->enElCrm()->post("/bixocrm/{$conv->id}/enviar", [
            'contenido' => 'Mira el catalogo',
            'archivo'   => UploadedFile::fake()->image('catalogo.jpg', 600, 400),
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['ok' => true]);

        $m = $conv->mensajes()->firstOrFail();
        $this->assertSame('imagen', $m->tipo);
        $this->assertSame('Mira el catalogo', $m->contenido);
        $this->assertStringContainsString('/storage/wa/' . $this->proyecto->id . '/', $m->media_url);
        Storage::disk('public')->assertExists('wa/' . $this->proyecto->id . '/' . basename($m->media_url));
        Http::assertSent(fn ($r) => $r['type'] === 'image' && $r['image']['link'] === $m->media_url && $r['image']['caption'] === 'Mira el catalogo');
    }

    public function test_un_pdf_se_envia_como_documento_con_su_nombre(): void
    {
        $conv = $this->conversacion();

        $this->enElCrm()->post("/bixocrm/{$conv->id}/enviar", [
            'archivo' => UploadedFile::fake()->create('cotizacion.pdf', 120, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();

        $m = $conv->mensajes()->firstOrFail();
        $this->assertSame('documento', $m->tipo);
        $this->assertSame('cotizacion.pdf', $m->contenido, 'Sin texto, el historial muestra el nombre del archivo.');
        Http::assertSent(fn ($r) => $r['type'] === 'document' && $r['document']['filename'] === 'cotizacion.pdf');
    }

    public function test_un_audio_mp3_se_envia_como_audio_sin_pie(): void
    {
        $conv = $this->conversacion();

        $this->enElCrm()->post("/bixocrm/{$conv->id}/enviar", [
            'archivo' => UploadedFile::fake()->create('nota.mp3', 80, 'audio/mpeg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $m = $conv->mensajes()->firstOrFail();
        $this->assertSame('audio', $m->tipo);
        Http::assertSent(fn ($r) => $r['type'] === 'audio' && str_ends_with($r['audio']['link'], '.mp3') && ! isset($r['audio']['caption']));
    }

    public function test_solo_imagenes_y_pdf_y_nunca_un_envio_vacio(): void
    {
        $conv = $this->conversacion();

        $this->enElCrm()->post("/bixocrm/{$conv->id}/enviar", [
            'archivo' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->enElCrm()->post("/bixocrm/{$conv->id}/enviar", ['contenido' => '   '], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame(0, $conv->mensajes()->count());
        Http::assertNothingSent();
    }

    public function test_reenviar_lleva_texto_o_adjunto_a_otra_conversacion_del_mismo_negocio(): void
    {
        $origen = $this->conversacion('51999111222');
        $destino = $this->conversacion('51999333444');
        $texto = $origen->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'Precio del taladro?', 'estado' => 'recibido']);
        $imagen = $origen->mensajes()->create(['direccion' => 'saliente', 'tipo' => 'imagen', 'contenido' => 'foto', 'media_url' => 'https://arindg.com/storage/wa/1/foto.jpg', 'estado' => 'enviado']);

        $this->enElCrm()->postJson("/bixocrm/{$origen->id}/reenviar", ['mensaje_id' => $texto->id, 'destino_id' => $destino->id])
            ->assertOk()->assertJson(['ok' => true]);
        $this->enElCrm()->postJson("/bixocrm/{$origen->id}/reenviar", ['mensaje_id' => $imagen->id, 'destino_id' => $destino->id])
            ->assertOk();

        $this->assertSame(2, $destino->mensajes()->count());
        $this->assertSame('https://arindg.com/storage/wa/1/foto.jpg', $destino->mensajes()->where('tipo', 'imagen')->value('media_url'));
        Http::assertSent(fn ($r) => $r['to'] === '51999333444' && ($r['text']['body'] ?? '') === 'Precio del taladro?');
        Http::assertSent(fn ($r) => $r['to'] === '51999333444' && ($r['image']['link'] ?? '') === 'https://arindg.com/storage/wa/1/foto.jpg');
    }

    public function test_no_se_reenvia_a_una_conversacion_de_otro_negocio(): void
    {
        $origen = $this->conversacion();
        $msg = $origen->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'hola', 'estado' => 'recibido']);

        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        $canalOtro = WaCanal::create(['project_id' => $otro->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '999', 'access_token' => 'T']);
        $ajena = WaConversacion::create(['wa_canal_id' => $canalOtro->id, 'cliente_nombre' => 'Ajeno', 'cliente_telefono' => '51900000000', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);

        $this->enElCrm()->postJson("/bixocrm/{$origen->id}/reenviar", ['mensaje_id' => $msg->id, 'destino_id' => $ajena->id])
            ->assertForbidden();
        $this->assertSame(0, $ajena->mensajes()->count());
    }
}
