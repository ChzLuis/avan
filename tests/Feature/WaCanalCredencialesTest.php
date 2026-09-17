<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\WaCanal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `wa_canales` es el propietario unico de las credenciales de WhatsApp.
 *
 * Se vigila que los secretos NO queden en claro en la base de datos y que una
 * linea de Meta no pueda quedar compartida entre dos negocios.
 */
class WaCanalCredencialesTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(string $nombre = 'Negocio'): Project
    {
        return Project::create([
            'name'     => $nombre,
            'slug'     => \Illuminate\Support\Str::slug($nombre) . '-' . uniqid(),
            'owner_id' => User::factory()->create()->id,
        ]);
    }

    private function canal(Project $p, string $phoneNumberId = '111'): WaCanal
    {
        return WaCanal::create([
            'project_id'      => $p->id,
            'nombre'          => 'Línea principal',
            'tipo'            => 'bixo',
            'phone_number_id' => $phoneNumberId,
            'access_token'    => 'TOKEN-SECRETO',
            'app_secret'      => 'APP-SECRETO',
            'verify_token'    => 'verif',
            'activo'          => true,
        ]);
    }

    /** Quien lea la base de datos NO debe obtener un token usable. */
    public function test_los_secretos_se_guardan_cifrados(): void
    {
        $canal = $this->canal($this->proyecto());

        $enBd = DB::table('wa_canales')->where('id', $canal->id)->first();
        $this->assertNotSame('TOKEN-SECRETO', $enBd->access_token);
        $this->assertNotSame('APP-SECRETO', $enBd->app_secret);

        // Y aun asi la aplicacion los lee bien.
        $this->assertSame('TOKEN-SECRETO', $canal->fresh()->access_token);
        $this->assertSame('APP-SECRETO', $canal->fresh()->app_secret);
    }

    /** Un dd() o un JSON no pueden filtrar el token. */
    public function test_los_secretos_no_salen_al_serializar(): void
    {
        $json = $this->canal($this->proyecto())->toArray();

        $this->assertArrayNotHasKey('access_token', $json);
        $this->assertArrayNotHasKey('app_secret', $json);
        $this->assertArrayNotHasKey('verify_token', $json);
    }

    /** Una linea de Meta atendiendo a dos negocios seria una fuga. */
    public function test_un_numero_no_puede_estar_en_dos_negocios(): void
    {
        $this->canal($this->proyecto('Negocio A'), '555');

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->canal($this->proyecto('Negocio B'), '555');
    }

    /** Un canal a medio configurar no debe intentar enviar. */
    public function test_sabe_si_esta_conectado_a_meta(): void
    {
        $proyecto = $this->proyecto();
        $this->assertTrue($this->canal($proyecto)->conectadoAMeta());

        $aMedias = WaCanal::create([
            'project_id' => $this->proyecto('Otro')->id,
            'nombre'     => 'Sin conectar',
            'tipo'       => 'bixo',
            'activo'     => true,
        ]);
        $this->assertFalse($aMedias->conectadoAMeta());
    }

    /** El panel muestra el token sin revelarlo entero. */
    public function test_el_token_se_muestra_enmascarado(): void
    {
        $canal = $this->canal($this->proyecto());

        $mostrado = $canal->tokenEnmascarado();
        $this->assertStringNotContainsString('TOKEN-SECRETO', $mostrado);
        $this->assertStringContainsString('•', $mostrado);
    }

    /** La version de la Graph API se toma del canal, no de una constante. */
    public function test_la_url_de_envio_usa_la_version_del_canal(): void
    {
        $canal = $this->canal($this->proyecto());

        $this->assertStringContainsString('v21.0', $canal->urlMensajes());
        $this->assertStringContainsString('111/messages', $canal->urlMensajes());

        $canal->forceFill(['api_version' => 'v22.0'])->saveQuietly();
        $this->assertStringContainsString('v22.0', $canal->fresh()->urlMensajes());
    }
}
