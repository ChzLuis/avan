<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\OrderEvent;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El portal público ante una cotización YA CONVERTIDA.
 *
 * Antes de F1c, `$statusMap` no tenía 'converted': caía en el fallback y el
 * cliente veía **"Borrador"** con botones para aceptar o rechazar algo que ya
 * era un pedido; al pulsar, el backend respondía "ya procesada". Con cinco
 * cotizaciones convertidas y token vivo en producción, era un fallo visible.
 */
class PortalQuoteConvertedTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Portal QA', 'slug' => 'portal-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function quote(array $attrs = []): Quote
    {
        $q = Quote::create(array_merge([
            'project_id' => $this->project->id, 'client_name' => 'Cliente Portal',
            'status' => 'converted', 'total' => '1266.00', 'token' => str()->random(32),
        ], $attrs));
        $q->items()->create(['description' => 'Equipo', 'price' => '769.00', 'quantity' => 1, 'discount' => 0]);

        return $q;
    }

    private function url(Quote $q): string
    {
        return '/b/' . $this->project->slug . '/c/' . $q->token;
    }

    public function test_una_convertida_se_presenta_como_convertida(): void
    {
        $html = $this->get($this->url($this->quote()))->assertSuccessful()->getContent();

        $this->assertStringContainsString('Convertida', $html);
        $this->assertStringNotContainsString('>Borrador<', $html,
            'una convertida no puede presentarse como borrador');
    }

    public function test_una_convertida_no_ofrece_aceptar_ni_rechazar(): void
    {
        $q = $this->quote();
        $html = $this->get($this->url($q))->assertSuccessful()->getContent();

        // El bloque de acciones se condiciona tambien por 'convertida'.
        $this->assertStringContainsString('!convertida', $html);
        $this->assertStringContainsString('convertida: true', $html);

        // Y el backend tampoco las admite.
        $this->postJson($this->url($q) . '/accept')->assertStatus(422);
        $this->postJson($this->url($q) . '/reject', ['reason' => 'x'])->assertStatus(422);
        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_el_comprobante_sigue_disponible_tras_convertir(): void
    {
        // Continuidad: que el vendedor genere el pedido no puede quitarle al
        // cliente la posibilidad, ya prometida, de subir su comprobante.
        $q = $this->quote(['payment_status' => 'pending']);

        $this->post($this->url($q) . '/proof', [
            'proof' => UploadedFile::fake()->image('voucher.jpg'),
        ])->assertSuccessful()->assertJson(['ok' => true]);

        $q->refresh();
        $this->assertNotNull($q->payment_proof_url);
        // F3c: el estado de pago no se mueve al subir comprobante — no se ha
        // cobrado nada. Lo que cambia es que ahora consta el comprobante.
        $this->assertSame('pending', $q->payment_status, 'reportar no es cobrar');
        $this->assertNotNull($q->payment_proof_at, 'el hecho real si queda registrado');
        $this->assertSame('converted', $q->status, 'subir comprobante no altera el estado comercial');
        $this->assertNull($q->order, 'ni toca la relacion con el pedido');
    }

    /** @dataProvider estadosDePago */
    public function test_el_comprobante_escribe_pago_canonico(string $inicial, string $esperado): void
    {
        $q = $this->quote(['status' => 'accepted', 'payment_status' => $inicial]);

        $this->post($this->url($q) . '/proof', [
            'proof' => UploadedFile::fake()->image('v.png'),
        ])->assertSuccessful();

        $this->assertSame($esperado, $q->fresh()->payment_status);
    }

    public static function estadosDePago(): array
    {
        return [
            // F3c: subir un comprobante NO cobra nada, asi que el estado de
            // pago NO cambia; solo se normaliza a canonico. Antes esto escribia
            // 'partial' —"pagada a medias" sin un solo sol cobrado y sin
            // importe—, la misma dolencia de los pedidos 34 y 35 pero generada
            // por el cliente. Lo que si queda registrado es el hecho real: el
            // comprobante, en `payment_proof_at`.
            'ya pagada canonica'    => ['paid', 'paid'],
            'ya pagada legacy'      => ['pagado', 'paid'],
            'pendiente canonica'    => ['pending', 'pending'],
            'parcial en español'    => ['parcial', 'partial'],
            'pendiente legacy'      => ['pendiente', 'pending'],
        ];
    }

    public function test_la_fecha_se_muestra_en_espanol_sin_locale_global(): void
    {
        $q = $this->quote(['status' => 'sent', 'valid_until' => '2026-08-26']);
        $q->forceFill(['created_at' => '2026-08-11 10:00:00'])->save();

        $html = $this->get($this->url($q))->assertSuccessful()->getContent();

        $this->assertStringContainsString('11 de agosto de 2026', $html);
        $this->assertStringContainsString('26 de agosto de 2026', $html);
        $this->assertStringNotContainsString('August', $html);

        // Sin efectos globales: la vista no puede tocar el locale del proceso.
        // Se busca la LLAMADA (con parentesis), no la palabra: si no, el propio
        // comentario que explica la decision haria fallar la prueba.
        $vista = file_get_contents(app_path('Modules/Tienda/Views/public/portal-quote.blade.php'));
        $this->assertStringNotContainsString('setlocale(', $vista);
        $this->assertStringNotContainsString('->locale(', $vista);
        $this->assertStringNotContainsString('Carbon::setLocale', $vista);
    }

    /**
     * Concurrencia: aceptar y rechazar comprobaban y escribian por separado.
     * Dos peticiones simultaneas podian pasar ambas y dejar la cotizacion con
     * dos eventos contradictorios. Ahora la transicion es condicional y solo
     * gana una: se simula ejecutando la segunda cuando el estado YA cambio.
     */
    public function test_solo_una_transicion_gana_en_el_portal(): void
    {
        $q = $this->quote(['status' => 'sent']);

        $this->postJson($this->url($q) . '/accept')->assertSuccessful();
        // La segunda llega con la cotizacion ya cerrada: debe rebotar.
        $this->postJson($this->url($q) . '/reject', ['reason' => 'tarde'])->assertStatus(422);

        $this->assertSame('accepted', $q->fresh()->status);
        $this->assertSame(1, OrderEvent::where('quote_id', $q->id)
            ->whereIn('action', ['accepted_by_client', 'rejected_by_client'])->count(),
            'un solo evento de respuesta del cliente');
    }

    public function test_la_transicion_condicional_no_muta_si_otro_gano(): void
    {
        // Se fuerza la carrera: se lee la cotizacion abierta, otro proceso la
        // cierra, y solo entonces se intenta escribir.
        $q = $this->quote(['status' => 'sent']);

        DB::table('quotes')->where('id', $q->id)->update(['status' => 'rejected']);

        $this->postJson($this->url($q) . '/accept')->assertStatus(422);
        $this->assertSame('rejected', $q->fresh()->status, 'el ganador conserva su estado');
        $this->assertSame(0, OrderEvent::where('quote_id', $q->id)
            ->where('action', 'accepted_by_client')->count(),
            'no se registra el evento del perdedor');
    }
}
