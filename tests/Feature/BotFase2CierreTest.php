<?php

namespace Tests\Feature;

use App\Models\BotFlow;
use App\Models\BotSession;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\WaCanal;
use App\Models\WaConversacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * FASE 2 — cierre de producción: identidad @lid, resiliencia ante fallos
 * internos, mensajes fuera de orden y recordatorios multi-línea.
 *
 * Nada de esto toca el motor conversacional (que ya tiene su batería en verde):
 * son los bordes operativos que separan "funciona en pruebas" de "aguanta
 * clientes reales".
 */
class BotFase2CierreTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Tienda Fase2',
            'slug'      => 'tienda-fase2',
            'is_active' => true,
        ]);
        $this->project->settings()->create(['key' => 'payment_yape_number', 'value' => '900000009']);
        Product::create(['project_id' => $this->project->id, 'name' => 'Cocina a gas', 'price' => 490, 'stock' => 3]);
        BotFlow::comercialDe($this->project)->update(['activo' => true]);
    }

    private function manda(string $telefono, string $mensaje, array $extra = [])
    {
        return $this->postJson('/api/bot/inbound', array_merge([
            'telefono' => $telefono, 'mensaje' => $mensaje, 'nombre' => 'Fase2',
        ], $extra), ['X-Copilot-Token' => $this->project->fresh()->copilot_token]);
    }

    private function convDe(string $telefono): ?WaConversacion
    {
        $canales = WaCanal::where('project_id', $this->project->id)->pluck('id');

        return WaConversacion::whereIn('wa_canal_id', $canales)
            ->where('cliente_telefono', $telefono)->first();
    }

    // ═══ IDENTIDAD @lid (LID-001 … LID-010) ═════════════════════════════════

    /** LID-001: número normal — camino de siempre, intacto. */
    public function test_lid_001_numero_normal(): void
    {
        $r = $this->manda('51955000100', 'hola');

        $this->assertNotEmpty($r->json('respuestas'));
        $this->assertNotNull(BotSession::where('project_id', $this->project->id)
            ->where('telefono', '51955000100')->first());
    }

    /** LID-002: entra SOLO con @lid (sin número conocido): se atiende igual. */
    public function test_lid_002_solo_lid_se_atiende(): void
    {
        // El conector, sin PN disponible, manda el id del lid como teléfono.
        $r = $this->manda('169531016216687', 'hola');

        $this->assertNotEmpty($r->json('respuestas'), 'Un @lid sin mapping también es un cliente.');
    }

    /**
     * LID-003: primero @lid, después llega el número real (WhatsApp entrega
     * ambos): la sesión y el CRM se FUSIONAN — un solo cliente, el hilo sigue.
     */
    public function test_lid_003_lid_primero_numero_despues_fusiona(): void
    {
        // Día 1: solo lid — abre menú (queda esperando).
        $this->manda('169531016216687', 'hola');
        $this->assertNotNull($this->convDe('169531016216687'));

        // Día 2: el mismo cliente llega con PN + lid.
        $r = $this->manda('51955354646', '3', ['lid' => '169531016216687']);

        // El "3" respondió el menú que quedó esperando: el ESTADO sobrevivió.
        $this->assertStringContainsString('900000009', json_encode($r->json('respuestas')),
            'La sesión del lid migró al número: el 3 eligió Métodos de pago.');

        // LID-005/006: una sola sesión y una sola conversación, bajo el número.
        $this->assertSame(1, BotSession::where('project_id', $this->project->id)->count());
        $this->assertSame('51955354646', BotSession::where('project_id', $this->project->id)->first()->telefono);
        $this->assertNull($this->convDe('169531016216687'), 'La conversación lid ya no existe aparte.');
        $conv = $this->convDe('51955354646');
        $this->assertNotNull($conv);
        $this->assertGreaterThanOrEqual(3, $conv->mensajes()->count(), 'El historial del lid se conservó.');
    }

    /** LID-004: primero número, después el mismo cliente aparece como lid+PN. */
    public function test_lid_004_numero_primero_lid_despues_no_duplica(): void
    {
        $this->manda('51955354646', 'hola');
        $this->manda('51955354646', 'tienen cocina a gas', ['lid' => '169531016216687']);

        $this->assertSame(1, BotSession::where('project_id', $this->project->id)->count());
        $canales = WaCanal::where('project_id', $this->project->id)->pluck('id');
        $this->assertSame(1, WaConversacion::whereIn('wa_canal_id', $canales)->count(),
            'Jamás dos clientes para la misma persona.');
    }

    /** LID-007: el handoff (vendedor asignado) también aplica tras la fusión. */
    public function test_lid_007_handoff_sobrevive_a_la_fusion(): void
    {
        \App\Models\Client::create([
            'project_id' => $this->project->id, 'name' => 'VIP',
            'phone' => '51955354646', 'responsable' => 'Vendedora Ana',
        ]);
        $this->manda('169531016216687', 'hola');

        // Fusiona identidades (el flujo del lid seguía a mitad: la regla, POR
        // DISEÑO, no corta un flujo en curso — aplica al siguiente arranque).
        $this->manda('51955354646', 'gracias', ['lid' => '169531016216687']);
        BotSession::where('project_id', $this->project->id)->update(['estado' => null]);

        // Próximo arranque, ya con la identidad real: el bot se calla.
        $r = $this->manda('51955354646', 'hola');

        $r->assertJson(['silenciado' => true]);
    }

    /** LID-008: con la identidad fusionada, el recordatorio tiene número real. */
    public function test_lid_008_recordatorio_tiene_numero_real(): void
    {
        $this->manda('169531016216687', 'hola');
        $this->manda('51955354646', 'gracias', ['lid' => '169531016216687']);

        $s = BotSession::where('project_id', $this->project->id)->first();
        $this->assertSame('51955354646', $s->telefono,
            'El seguimiento saldría al número real, no al id técnico.');
    }

    /** LID-010: dos clientes con lids DISTINTOS jamás se mezclan. */
    public function test_lid_010_dos_lids_distintos_no_se_mezclan(): void
    {
        $this->manda('169531016216687', 'tienen cocina a gas');
        $this->manda('269531016299999', 'aceptan yape?');

        $this->assertSame(2, BotSession::where('project_id', $this->project->id)->count());
        $canales = WaCanal::where('project_id', $this->project->id)->pluck('id');
        $this->assertSame(2, WaConversacion::whereIn('wa_canal_id', $canales)->count());
    }

    // ═══ BD CAÍDA / FALLO INTERNO (DB-001 …) ════════════════════════════════

    /** DB-001: consulta con la tabla de productos rota → disculpa, no SQLSTATE. */
    public function test_db_001_fallo_de_consulta_no_filtra_sql_al_cliente(): void
    {
        $this->manda('51955000200', 'hola');
        Schema::drop('categories');

        $r = $this->manda('51955000200', 'cuanto cuesta la cocina');

        $r->assertOk();
        $txt = json_encode($r->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('SQLSTATE', $txt);
        $this->assertStringNotContainsString('Exception', $txt);
        $this->assertStringNotContainsString('categories', $txt, 'Ni nombres de tablas.');
        $this->assertStringContainsString('asesor', $txt, 'Disculpa breve con salida humana.');
        $this->assertTrue((bool) $r->json('error_interno'));
    }

    /** DB-002: sin tabla de sesiones → el saludo degrada controlado. */
    public function test_db_002_fallo_de_sesion_degrada_controlado(): void
    {
        Schema::drop('bot_sessions');

        $r = $this->manda('51955000300', 'hola');

        $r->assertOk();
        $txt = json_encode($r->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('SQLSTATE', $txt);
        $this->assertStringNotContainsString('Stack trace', $txt);
    }

    /** DB-003: el fallo interno NO rompe la idempotencia ni el auth. */
    public function test_db_003_token_invalido_sigue_siendo_401(): void
    {
        $this->postJson('/api/bot/inbound', ['telefono' => 'x', 'mensaje' => 'hola'],
            ['X-Copilot-Token' => 'malo'])->assertStatus(401);
    }

    // ═══ FUERA DE ORDEN (OOO-001) ═══════════════════════════════════════════

    /**
     * Política documentada: el transporte no trae timestamp confiable, así que
     * se procesa EN ORDEN DE LLEGADA. Lo que se garantiza: nunca se rompe ni
     * contamina estado; cada mensaje recibe una respuesta coherente con lo que
     * el bot sabe en ese momento.
     */
    public function test_ooo_001_mensajes_invertidos_no_rompen_ni_contaminan(): void
    {
        // El cliente escribió A ("tienen cocina a gas") y B ("cuanto cuesta?"),
        // pero llegan B → A.
        $rb = $this->manda('51955000400', 'cuanto cuesta?');
        $ra = $this->manda('51955000400', 'tienen cocina a gas');

        $tb = json_encode($rb->json('respuestas'), JSON_UNESCAPED_UNICODE);
        $ta = json_encode($ra->json('respuestas'), JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('SQLSTATE', $tb . $ta);
        $this->assertStringContainsString('busca', mb_strtolower($tb), 'B sin contexto: pide qué busca.');
        $this->assertStringContainsString('No encontré una categoría', $ta, 'A se responde honesto al llegar.');
    }

    // ═══ RECORDATORIOS MULTI-LÍNEA (REC-001 … REC-005) ══════════════════════

    private function enviarRecordatorio(Project $p, string $tel = '51955000500'): bool
    {
        $cmd = new \App\Console\Commands\SeguimientoConversaciones();
        $cmd->setLaravel(app());
        $cmd->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput()
        ));
        $m = new \ReflectionMethod($cmd, 'enviar');
        $m->setAccessible(true);

        return $m->invoke($cmd, $p, $tel, 'recordatorio de prueba');
    }

    /** REC-001/002/003: cada proyecto sale por SU conector, nunca por el ajeno. */
    public function test_rec_001_cada_proyecto_usa_su_propio_conector(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $b = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Linea B', 'slug' => 'linea-b-' . uniqid(), 'is_active' => true,
        ]);
        $this->project->settings()->create(['key' => 'wa_connector_url', 'value' => 'http://127.0.0.1:8789']);
        $b->settings()->create(['key' => 'wa_connector_url', 'value' => 'http://127.0.0.1:8788']);

        $this->assertTrue($this->enviarRecordatorio($this->project));
        $this->assertTrue($this->enviarRecordatorio($b));

        Http::assertSent(fn ($req) => str_starts_with($req->url(), 'http://127.0.0.1:8789/enviar')
            && $req['token'] === $this->project->fresh()->copilot_token);
        Http::assertSent(fn ($req) => str_starts_with($req->url(), 'http://127.0.0.1:8788/enviar')
            && $req['token'] === $b->fresh()->copilot_token);
        Http::assertNotSent(fn ($req) => str_starts_with($req->url(), 'http://127.0.0.1:8788/')
            && $req['token'] === $this->project->fresh()->copilot_token);
    }

    /** REC-004: proyecto sin conector (ni global) → no envía, sin excepción. */
    public function test_rec_004_sin_conector_error_controlado(): void
    {
        Http::fake();
        config(['services.wa_connector.url' => null]);

        $ok = $this->enviarRecordatorio($this->project);

        $this->assertFalse($ok);
        Http::assertNothingSent();
    }

    /** REC-005: conector caído → false controlado, sin tumbar el comando. */
    public function test_rec_005_conector_caido_controlado(): void
    {
        Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('rechazado')]);
        $this->project->settings()->create(['key' => 'wa_connector_url', 'value' => 'http://127.0.0.1:8799']);

        $this->assertFalse($this->enviarRecordatorio($this->project));
    }

    // ═══ IA SIGUE APAGADA TRAS TODA LA FASE (sección 10) ════════════════════

    public function test_ia_sigue_sin_consumir_nada(): void
    {
        Http::fake();

        // Frase libre larga que ANTES habría tentado a la IA: sin licencia,
        // ni un byte al proveedor.
        $this->manda('51955000600', 'hola');
        $this->manda('51955000600', 'quisiera saber si ustedes tienen algo bonito para regalar a mi señora madre');

        Http::assertNothingSent();
    }
}
