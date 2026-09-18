<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\ConsultaDocumento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Consulta de DNI/RUC — punto único (2026-09-03).
 *
 * Nace de una auditoría que encontró la integración ROTA: el RUC exigía un
 * ajuste (`apiperu_token`) que ningún proyecto tenía, y el DNI vivía en
 * un controlador ya retirado, tras un permiso ajeno — un cajero no podía usarlo.
 *
 * La regla que se protege aquí: pase lo que pase con la API, la respuesta es
 * una estructura estable con un mensaje entendible, y el usuario SIEMPRE
 * puede seguir escribiendo los datos a mano.
 */
class ConsultaDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private ConsultaDocumento $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Doc QA', 'slug' => 'doc-qa', 'is_active' => true,
        ]);
        $this->servicio = new ConsultaDocumento();
    }

    // ── Validación local: no se gasta cuota en documentos imposibles ──────

    public static function documentosInvalidos(): array
    {
        return [
            'vacío'          => [''],
            'DNI incompleto' => ['1234567'],
            'RUC incompleto' => ['2060081911'],
            'largo raro'     => ['123456789'],
            'solo letras'    => ['ABCDEFGH'],
        ];
    }

    /** @dataProvider documentosInvalidos */
    public function test_no_sale_a_internet_con_un_documento_invalido(string $numero): void
    {
        Http::fake();

        $r = $this->servicio->consultar($this->project, $numero);

        $this->assertFalse($r['ok']);
        $this->assertSame('invalido', $r['motivo']);
        Http::assertNothingSent();
    }

    public function test_el_digito_verificador_descarta_un_ruc_mal_escrito(): void
    {
        Http::fake();

        // 11 dígitos pero con el verificador cambiado.
        $r = $this->servicio->consultar($this->project, '20600819111');

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('no es válido', $r['mensaje']);
        Http::assertNothingSent();
    }

    public function test_reconoce_un_ruc_real_como_bien_formado(): void
    {
        $this->assertTrue(ConsultaDocumento::rucBienFormado('20600819110'));
        $this->assertFalse(ConsultaDocumento::rucBienFormado('99600819110'), 'Tipo de contribuyente inexistente');
    }

    // ── RUC: el camino que hoy funciona sin token ─────────────────────────

    public function test_el_ruc_devuelve_los_datos_completos_que_pide_el_comprobante(): void
    {
        Http::fake(['api.apis.net.pe/*' => Http::response([
            'nombre'     => 'DISTRIBUIDORES GABDE E.I.R.L.',
            'direccion'  => 'JR. HUAROCHIRI CUADRA 05',
            'estado'     => 'ACTIVO',
            'condicion'  => 'HABIDO',
            'ubigeo'     => '150101',
            'distrito'   => 'LIMA',
            'provincia'  => 'LIMA',
            'departamento' => 'LIMA',
        ], 200)]);

        $r = $this->servicio->consultar($this->project, '20600819110');

        $this->assertTrue($r['ok']);
        $this->assertSame('ruc', $r['tipo']);
        $this->assertSame('DISTRIBUIDORES GABDE E.I.R.L.', $r['datos']['razon_social']);
        foreach (['direccion', 'estado', 'condicion', 'ubigeo', 'distrito', 'provincia', 'departamento'] as $campo) {
            $this->assertNotSame('', $r['datos'][$campo], "Falta {$campo}");
        }
    }

    public function test_el_mismo_ruc_no_se_consulta_dos_veces(): void
    {
        Http::fake(['api.apis.net.pe/*' => Http::response(['nombre' => 'EMPRESA X'], 200)]);

        $this->servicio->consultar($this->project, '20600819110');
        $this->servicio->consultar($this->project, '20600819110');

        Http::assertSentCount(1);
    }

    /**
     * SUNAT publica el domicilio de las empresas (RUC 20) pero NO el de una
     * persona natural con negocio (RUC 10): el proveedor rellena esos campos
     * con un guion. Ese guion no puede acabar impreso en un comprobante.
     */
    public function test_el_guion_del_proveedor_no_se_toma_por_una_direccion(): void
    {
        Http::fake(['api.apis.net.pe/*' => Http::response([
            'nombre' => 'ZAPATA OSORIO ZAIDA', 'estado' => 'ACTIVO', 'condicion' => 'HABIDO',
            'direccion' => '-', 'ubigeo' => '-', 'distrito' => '', 'provincia' => '', 'departamento' => '',
        ], 200)]);

        $r = $this->servicio->consultar($this->project, '10476818953');

        $this->assertTrue($r['ok'], 'El contribuyente existe: la consulta es un acierto');
        $this->assertSame('ZAPATA OSORIO ZAIDA', $r['datos']['razon_social']);
        $this->assertSame('', $r['datos']['direccion'], 'El guion debe quedar como vacío, no como dirección');
        $this->assertSame('', $r['datos']['ubigeo']);
        // Lo que sí publica, se conserva.
        $this->assertSame('ACTIVO', $r['datos']['estado']);
    }

    // ── Errores: cada uno con su mensaje, ninguno técnico ─────────────────

    public static function erroresDelServicio(): array
    {
        return [
            'no existe'      => [404, 'no_encontrado'],
            'saturado'       => [429, 'limite'],
            'caído'          => [500, 'servicio'],
            'no autorizado'  => [403, 'servicio'],
        ];
    }

    /** @dataProvider erroresDelServicio */
    public function test_cada_fallo_del_servicio_se_explica_en_castellano(int $http, string $motivo): void
    {
        Http::fake(['api.apis.net.pe/*' => Http::response('', $http)]);

        $r = $this->servicio->consultar($this->project, '20600819110');

        $this->assertFalse($r['ok']);
        $this->assertSame($motivo, $r['motivo']);
        // Nunca un código técnico en la cara del usuario.
        $this->assertStringNotContainsString('HTTP', $r['mensaje']);
        $this->assertStringNotContainsString((string) $http, $r['mensaje']);
    }

    public function test_una_respuesta_vacia_no_se_toma_por_buena(): void
    {
        Http::fake(['api.apis.net.pe/*' => Http::response([], 200)]);

        $r = $this->servicio->consultar($this->project, '20600819110');

        $this->assertFalse($r['ok']);
        $this->assertSame('vacio', $r['motivo']);
    }

    public function test_si_el_servicio_no_responde_se_puede_seguir_a_mano(): void
    {
        Http::fake(fn () => throw new \RuntimeException('sin red'));

        $r = $this->servicio->consultar($this->project, '20600819110');

        $this->assertFalse($r['ok']);
        $this->assertSame('conexion', $r['motivo']);
        $this->assertStringContainsString('a mano', $r['mensaje']);
    }

    // ── DNI: exige token desde que el proveedor cambió de endpoint ────────

    public function test_sin_token_el_dni_lo_dice_claro_y_no_llama(): void
    {
        config(['services.apisperu.doc_token' => null]);
        Http::fake();

        $r = $this->servicio->consultar($this->project, '10000000');

        $this->assertFalse($r['ok']);
        $this->assertSame('sin_token', $r['motivo']);
        $this->assertStringContainsString('a mano', $r['mensaje']);
        Http::assertNothingSent();
    }

    public function test_con_token_el_dni_arma_el_nombre_completo(): void
    {
        $this->project->settings()->create(['key' => 'apiperu_token', 'value' => 'tok-123']);
        Http::fake(['api.apis.net.pe/*' => Http::response([
            'nombres' => 'JUAN CARLOS', 'apellidoPaterno' => 'PEREZ', 'apellidoMaterno' => 'GOMEZ',
        ], 200)]);

        $r = $this->servicio->consultar($this->project->fresh(), '10000000');

        $this->assertTrue($r['ok']);
        $this->assertSame('dni', $r['tipo']);
        $this->assertSame('JUAN CARLOS PEREZ GOMEZ', $r['datos']['nombre_completo']);
    }

    public function test_un_token_caducado_se_distingue_de_un_dni_inexistente(): void
    {
        $this->project->settings()->create(['key' => 'apiperu_token', 'value' => 'tok-viejo']);
        Http::fake(['api.apis.net.pe/*' => Http::response(['message' => 'Invalid token'], 401)]);

        $r = $this->servicio->consultar($this->project->fresh(), '10000000');

        $this->assertSame('token_invalido', $r['motivo']);
        $this->assertStringContainsString('caducó', $r['mensaje']);
    }
}
