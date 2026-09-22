<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Cerrar sesión en el portal Comercial (2026-09-22).
 *
 * El usuario: "tampoco puedo cerrar sesión".
 *
 * `AuthController::logout()` solo hacía `session()->forget('comercial_project_id')`
 * — olvidaba qué negocio estaba abierto, pero NUNCA cerraba la sesión del
 * usuario: sin `Auth::logout()`, sin invalidar la sesión y sin regenerar el
 * token. El usuario seguía autenticado, así que volvía a entrar solo.
 *
 * Es además un asunto de seguridad: en una computadora compartida, "cerrar
 * sesión" tiene que cerrarla de verdad.
 */
class CerrarSesionComercialTest extends TestCase
{
    use RefreshDatabase;

    private function negocio(): array
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $project = Project::create([
            'owner_id' => $user->id, 'name' => 'Ferretería QA',
            'slug' => 'cs-'.uniqid(), 'is_active' => true,
        ]);

        return [$user, $project];
    }

    /** Cerrar sesión desautentica de verdad, no solo olvida el negocio. */
    public function test_cerrar_sesion_desautentica_al_usuario(): void
    {
        [$user, $project] = $this->negocio();

        $this->actingAs($user)
            ->withSession(['comercial_project_id' => $project->id])
            ->post(route('bixosales.logout'))
            ->assertRedirect(route('bixosales.login'));

        // assertGuest() recibe el GUARD, no un mensaje: el usuario debe quedar
        // desautenticado al cerrar sesion.
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    /** Y deja de recordar qué negocio estaba abierto. */
    public function test_olvida_el_negocio_abierto(): void
    {
        [$user, $project] = $this->negocio();

        $this->actingAs($user)
            ->withSession(['comercial_project_id' => $project->id])
            ->post(route('bixosales.logout'));

        $this->assertNull(session('comercial_project_id'));
    }

    /**
     * Tras cerrar sesión, el panel ya no se abre.
     *
     * Es la prueba que de verdad importa: antes el usuario seguia autenticado,
     * asi que volvia a entrar sin pedir contraseña.
     */
    public function test_despues_de_salir_el_panel_pide_entrar(): void
    {
        [$user, $project] = $this->negocio();

        $sesion = $this->actingAs($user)->withSession(['comercial_project_id' => $project->id]);
        $sesion->post(route('bixosales.logout'));

        $this->get(route('bixosales.dashboard'))->assertRedirect();
        $this->assertGuest();
    }

    /**
     * El CRM tenía el MISMO agujero y se arregló a la vez.
     *
     * `CrmAuthController::logout()` tambien se limitaba a olvidar el negocio
     * (`comunicaciones_project_id`) dejando la sesion viva.
     */
    public function test_el_crm_tambien_cierra_de_verdad(): void
    {
        [$user, $project] = $this->negocio();

        $this->actingAs($user)
            ->withSession(['comunicaciones_project_id' => $project->id])
            ->post(route('bixocrm.logout'))
            ->assertRedirect(route('bixocrm.login'));

        $this->assertGuest();
        $this->assertNull(session('comunicaciones_project_id'));
    }

    /**
     * El boton tiene que VERSE en escritorio.
     *
     * Arreglar que el logout cerrara la sesion no servia de nada si no habia
     * donde pulsarlo: el unico boton vivia en el cajon movil (`.nav-acciones`,
     * con display:none en escritorio) y en los modales de INACTIVIDAD, que
     * solo salen cuando la sesion va a expirar.
     */
    public function test_hay_donde_cerrar_sesion_en_escritorio(): void
    {
        $shell = file_get_contents(base_path('resources/views/comercial/layouts/app.blade.php'));

        // El menu de cuenta del chip de empresa, que no depende del cajon movil.
        $this->assertStringContainsString("route('bixosales.logout')", $shell,
            'la cabecera debe tener el formulario de cerrar sesion');
        $this->assertStringContainsString('Cerrar sesión', $shell);

        // Y no puede estar SOLO dentro de los modales de inactividad.
        $antesDeModales = substr($shell, 0, strpos($shell, "x-show=\"phase==='expired'\"") ?: strlen($shell));
        $this->assertStringContainsString("route('bixosales.logout')", $antesDeModales,
            'el boton no puede vivir solo en el modal de sesion expirada');
    }

    /** El cierre por inactividad avisa, y también desautentica. */
    public function test_el_cierre_por_inactividad_tambien_cierra(): void
    {
        [$user, $project] = $this->negocio();

        $this->actingAs($user)
            ->withSession(['comercial_project_id' => $project->id])
            ->post(route('bixosales.logout'), ['_inactivity' => 1])
            ->assertRedirect(route('bixosales.login'))
            ->assertSessionHas('inactivity', true);

        $this->assertGuest();
    }
}
