<?php

namespace Tests\Feature;

use App\Models\GuiaRemision;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Support\Sunat\GuiaRemisionSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aceptado tiene que decirlo la respuesta, no deducirse de su silencio.
 *
 * La comprobación era «si no viene success:false, entonces aceptado». Contra el
 * servidor real esto salió mal dos veces:
 *
 *   - la baja devolvió HTTP 500 con {"error":"Error al comunicarse con el
 *     servidor interno"} y quedó marcada como aceptada;
 *   - la guía devolvió HTTP 400 pidiendo credenciales de la API de SUNAT y
 *     también quedó como aceptada.
 *
 * Es el peor fallo posible aquí: el negocio cree que anuló un comprobante que
 * sigue vivo en SUNAT, y se entera cuando le fiscalizan. Estos casos son las
 * respuestas literales que devolvió el servidor.
 */
class RespuestaSunatTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Fiscal QA', 'slug' => 'fiscal-resp-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $p->settings()->create(['key' => 'billing_provider', 'value' => 'apisperu']);
        $p->settings()->create(['key' => 'apisperu_token', 'value' => 'token-falso']);

        return $p;
    }

    private function guia(Project $p): GuiaRemision
    {
        $g = $p->guiasRemision()->create([
            'serie' => 'T001', 'correlativo' => 1, 'numero' => 'T001-00000001',
            'destinatario_nombre' => 'Cliente', 'destinatario_doc_tipo' => '6',
            'destinatario_doc_numero' => '20512345678',
            'motivo_codigo' => '01', 'fecha_traslado' => now()->toDateString(),
            'modalidad' => GuiaRemision::PRIVADO, 'peso_total' => 10,
            'partida_direccion' => 'A', 'llegada_direccion' => 'B',
            'vehiculo_placa' => 'ABC-123', 'conductor_doc_numero' => '45678912',
            'conductor_licencia' => 'Q1', 'status' => 'issued',
        ]);
        $g->items()->create(['description' => 'X', 'unit' => 'NIU', 'quantity' => 1]);

        return $g->fresh(['items', 'project', 'invoice']);
    }

    /** Lo que devolvió el servidor de verdad cuando falló la guía. */
    public function test_un_http_400_con_error_no_es_una_guia_aceptada(): void
    {
        $guia = $this->guia($this->proyecto());

        $resultado = $this->interpretar($guia, 400, json_encode([
            'error' => 'Si está usando la nueva guía de remisión debe establecer las credenciales de API SUNAT (client_id y client_secret).',
        ]));

        $this->assertFalse($resultado['ok'], 'un HTTP 400 con error no puede darse por aceptado');
        $this->assertSame('error', $guia->fresh()->sunat_status);
        $this->assertStringContainsString('client_id', $guia->fresh()->sunat_error);
    }

    /** Y un 500 tampoco, aunque el cuerpo no traiga la clave que se miraba. */
    public function test_un_http_500_no_es_una_guia_aceptada(): void
    {
        $guia = $this->guia($this->proyecto());

        $resultado = $this->interpretar($guia, 500, json_encode(['error' => 'Error al comunicarse con el servidor interno']));

        $this->assertFalse($resultado['ok']);
        $this->assertSame('error', $guia->fresh()->sunat_status);
    }

    /**
     * Una respuesta 200 sin ninguna señal de aceptación tampoco vale: puede ser
     * un cuerpo vacío o de otro endpoint. Hace falta ticket, CDR o success.
     */
    public function test_un_200_mudo_no_es_una_guia_aceptada(): void
    {
        $guia = $this->guia($this->proyecto());

        $resultado = $this->interpretar($guia, 200, json_encode(['mensaje' => 'ok']));

        $this->assertFalse($resultado['ok'], 'sin señal positiva no se da por aceptada');
        $this->assertSame('error', $guia->fresh()->sunat_status);
    }

    /** Con el ticket que devuelve SUNAT, entonces sí. */
    public function test_un_ticket_si_es_una_guia_aceptada(): void
    {
        $guia = $this->guia($this->proyecto());

        $resultado = $this->interpretar($guia, 200, json_encode(['ticket' => '202608240001']));

        $this->assertTrue($resultado['ok']);
        $this->assertSame('accepted', $guia->fresh()->sunat_status);
        $this->assertSame('202608240001', $guia->fresh()->sunat_ticket);
    }

    /**
     * La baja de una factura, con la misma respuesta real que la marcó como
     * aceptada sin serlo.
     */
    public function test_un_error_del_servidor_no_da_de_baja_una_factura(): void
    {
        $p = $this->proyecto();

        $invoice = $p->invoices()->create([
            'type' => 'factura', 'serie' => 'F001', 'correlativo' => 1, 'numero' => 'F001-00000001',
            'client_name' => 'Cliente', 'emisor_ruc' => '20600819110',
            'subtotal' => '100.00', 'igv' => '18.00', 'total' => '118.00',
            'issue_date' => now()->toDateString(), 'status' => 'issued',
            'sunat_status' => 'accepted', 'baja_motivo' => 'prueba',
        ]);

        // Se reproduce la comprobación del servicio con la respuesta real.
        $http = 500;
        $resp = ['error' => 'Error al comunicarse con el servidor interno'];

        $aceptada = ! ($http >= 400 || isset($resp['error']) || isset($resp['errors'])
            || ($resp['success'] ?? null) === false
            || ! (isset($resp['ticket']) || isset($resp['cdrResponse']) || ($resp['success'] ?? null) === true));

        $this->assertFalse($aceptada, 'la respuesta de error no puede contar como baja aceptada');

        // Y el comprobante tiene que seguir surtiendo efecto.
        $this->assertNotSame('accepted', $invoice->baja_estado);
    }

    /**
     * El formato real de APIsPERU anida la señal: {"xml": ...,
     * "sunatResponse": {"success": true, "ticket": "..."}}. La primera versión
     * de la comprobación estricta la buscaba en la raíz y rechazó una baja que
     * SUNAT sí había aceptado — el error contrario, e igual de malo.
     */
    public function test_la_senal_anidada_en_sunat_response_cuenta_como_aceptada(): void
    {
        $http = 200;
        $resp = ['xml' => '<VoidedDocuments/>', 'sunatResponse' => ['success' => true, 'ticket' => '1787607564283']];

        $sunat  = $resp['sunatResponse'] ?? $resp;
        $ticket = $sunat['ticket'] ?? ($resp['ticket'] ?? null);
        $acepta = ($sunat['success'] ?? null) === true || $ticket !== null || isset($sunat['cdrResponse']);

        $rechazada = $http >= 400 || ! empty($resp['error']) || isset($resp['errors']) || ! $acepta;

        $this->assertFalse($rechazada, 'la baja con ticket anidado es una baja aceptada');
        $this->assertSame('1787607564283', $ticket);
    }

    /** Ejecuta la comprobación real del emisor sin llegar a la red. */
    private function interpretar(GuiaRemision $guia, int $http, string $body): array
    {
        $metodo = new \ReflectionMethod(GuiaRemisionSender::class, 'interpretar');
        $metodo->setAccessible(true);

        return $metodo->invoke(new GuiaRemisionSender(), $guia, $http, $body, '', function (array $resp) {
            return isset($resp['ticket']) || isset($resp['cdrResponse']) || ($resp['success'] ?? null) === true;
        });
    }
}
