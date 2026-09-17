<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WaCanal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pantalla de Canales del CRM: donde el negocio pega sus credenciales de Meta.
 *
 * Se vigila que el App Secret (lo que firma los webhooks) se pueda configurar
 * desde el panel, que viaje cifrado, que editar sin tocarlo lo conserve, y
 * que la respuesta de guardado no devuelva secretos al navegador.
 */
class CanalesCredencialesMetaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario  = User::factory()->create();
        $this->proyecto = Project::create([
            'name'     => 'Negocio CRM',
            'slug'     => 'negocio-crm-' . uniqid(),
            'owner_id' => $this->usuario->id,
        ]);
    }

    /** Entra al portal de Comunicaciones como lo hace el login real. */
    private function enElCrm()
    {
        return $this->actingAs($this->usuario)
            ->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    private function datosCanal(array $extra = []): array
    {
        return array_merge([
            'nombre'          => 'Línea principal',
            'tipo'            => 'bixo',
            'phone_number_id' => '123456789',
            'access_token'    => 'TOKEN-META',
            'app_secret'      => 'SECRETO-APP',
            'verify_token'    => 'verif-abc',
        ], $extra);
    }

    public function test_la_pantalla_ofrece_el_campo_app_secret(): void
    {
        $this->enElCrm()
            ->get('/bixocrm/configuracion')
            ->assertOk()
            ->assertSee('App Secret');
    }

    public function test_guardar_cifra_el_app_secret(): void
    {
        $this->enElCrm()
            ->postJson('/bixocrm/canales', $this->datosCanal())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $canal = WaCanal::where('project_id', $this->proyecto->id)->firstOrFail();
        $enBd  = DB::table('wa_canales')->where('id', $canal->id)->first();

        $this->assertNotSame('SECRETO-APP', $enBd->app_secret, 'El App Secret quedó en claro en la base.');
        $this->assertSame('SECRETO-APP', $canal->app_secret);
        $this->assertSame('TOKEN-META', $canal->access_token);
    }

    /** Los secretos no se muestran al editar: en blanco significa "conservar". */
    public function test_editar_sin_secretos_conserva_los_anteriores(): void
    {
        $this->enElCrm()->postJson('/bixocrm/canales', $this->datosCanal())->assertOk();
        $canal = WaCanal::where('project_id', $this->proyecto->id)->firstOrFail();

        $this->enElCrm()
            ->postJson('/bixocrm/canales', $this->datosCanal([
                'id'           => $canal->id,
                'nombre'       => 'Línea renombrada',
                'access_token' => '',
                'app_secret'   => '',
                'verify_token' => '',
            ]))
            ->assertOk();

        $canal->refresh();
        $this->assertSame('Línea renombrada', $canal->nombre);
        $this->assertSame('SECRETO-APP', $canal->app_secret, 'Editar sin tocar el App Secret lo borró.');
        $this->assertSame('TOKEN-META', $canal->access_token, 'Editar sin tocar el token lo borró.');
        $this->assertSame('verif-abc', $canal->verify_token);
    }

    /** Guardar no devuelve el token al navegador: el formulario no lo necesita. */
    public function test_la_respuesta_no_devuelve_secretos(): void
    {
        $json = $this->enElCrm()
            ->postJson('/bixocrm/canales', $this->datosCanal())
            ->assertOk()
            ->json('canal');

        $this->assertArrayNotHasKey('access_token', $json);
        $this->assertArrayNotHasKey('app_secret', $json);
    }

    /** El último rechazo de Meta se ve en la tarjeta, sin abrir logs. */
    public function test_la_tarjeta_muestra_el_ultimo_error_de_meta(): void
    {
        WaCanal::create($this->datosCanal(['project_id' => $this->proyecto->id]))
            ->forceFill(['ultimo_error' => 'Error validating access token: Session has expired'])
            ->saveQuietly();

        $this->enElCrm()
            ->get('/bixocrm/configuracion')
            ->assertOk()
            ->assertSee('Session has expired');
    }
}
