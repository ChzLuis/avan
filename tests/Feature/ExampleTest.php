<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // El layout de invitado consulta `projects` al pintar el login.
    use RefreshDatabase;

    /**
     * La raiz NO es una pagina publica: este es un panel, y `/` manda al login.
     * El ejemplo por defecto de Laravel daba por hecho un 200 que aqui nunca
     * fue cierto, y por eso llevaba en rojo desde el principio.
     */
    public function test_la_raiz_lleva_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login', absolute: false));
    }

    /** Y el login si responde: la aplicacion arranca. */
    public function test_el_login_responde(): void
    {
        $this->get(route('login'))->assertSuccessful();
    }
}
