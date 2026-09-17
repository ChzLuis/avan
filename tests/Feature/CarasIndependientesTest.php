<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\ContextoProyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Configuración y Operación son negocios independientes (2026-09-04).
 *
 * Las dos caras conviven en la misma sesión del navegador, cada una con su
 * clave. Antes los tres sitios que resolvían el contexto hacían
 * `active_project_id ?? comercial_project_id`, así que elegir un negocio en
 * Configuración se lo llevaba puesto a Operación —donde el usuario ya había
 * elegido otro en su propio login—.
 */
class CarasIndependientesTest extends TestCase
{
    use RefreshDatabase;

    private Project $configuracion;
    private Project $operacion;

    protected function setUp(): void
    {
        parent::setUp();

        $dueno = User::factory()->create();
        $this->configuracion = Project::create([
            'owner_id' => $dueno->id, 'name' => 'Negocio A', 'slug' => 'negocio-a', 'is_active' => true,
        ]);
        $this->operacion = Project::create([
            'owner_id' => $dueno->id, 'name' => 'Negocio B', 'slug' => 'negocio-b', 'is_active' => true,
        ]);
    }

    /** Simula una petición a una URL concreta con las dos claves puestas. */
    private function contextoEn(string $url): ?int
    {
        session([
            'active_project_id'    => $this->configuracion->id,
            'comercial_project_id' => $this->operacion->id,
        ]);

        $this->app['request'] = \Illuminate\Http\Request::create($url);

        return ContextoProyecto::id();
    }

    public function test_en_operacion_manda_el_negocio_de_operacion(): void
    {
        $this->assertSame($this->operacion->id, $this->contextoEn('/bixosales/facturas'));
    }

    public function test_en_configuracion_manda_el_negocio_de_configuracion(): void
    {
        $this->assertSame($this->configuracion->id, $this->contextoEn('/invoices'));
    }

    public function test_cambiar_de_negocio_en_configuracion_no_arrastra_a_operacion(): void
    {
        // El escenario que se reportó: elegías un negocio en el panel y
        // Operación te lo cambiaba por debajo, ignorando tu login.
        $this->contextoEn('/bixoadmin');
        session(['active_project_id' => $this->configuracion->id]);

        $this->app['request'] = \Illuminate\Http\Request::create('/bixosales/pedidos');

        $this->assertSame($this->operacion->id, ContextoProyecto::id(),
            'Operación debe seguir en SU negocio');
    }

    public function test_sin_sesion_de_operacion_se_usa_la_otra(): void
    {
        /* Quien entra a Operación sin haber pasado por su login no puede
           quedarse sin contexto: se cae a la clave que exista. */
        session()->forget('comercial_project_id');
        session(['active_project_id' => $this->configuracion->id]);

        $this->app['request'] = \Illuminate\Http\Request::create('/bixosales/facturas');

        $this->assertSame($this->configuracion->id, ContextoProyecto::id());
    }

    public function test_sin_ninguna_sesion_no_hay_contexto(): void
    {
        session()->forget(['active_project_id', 'comercial_project_id']);

        $this->app['request'] = \Illuminate\Http\Request::create('/invoices');

        $this->assertNull(ContextoProyecto::id());
    }

    public function test_la_cara_se_reconoce_por_la_url(): void
    {
        $this->app['request'] = \Illuminate\Http\Request::create('/bixosales/pos');
        $this->assertTrue(ContextoProyecto::esOperacion());

        $this->app['request'] = \Illuminate\Http\Request::create('/invoices');
        $this->assertFalse(ContextoProyecto::esOperacion());
    }
}
