<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Ninguna ruta pública de escritura queda sin límite de envíos (2026-09-07).
 *
 * Los formularios de la tienda (contacto, reclamaciones, reseñas, pedidos)
 * crean registros y mandan correos sin que nadie inicie sesión. Sin tope,
 * cualquiera automatiza miles de envíos y llena la base de datos y el buzón
 * del comerciante. Solo `order-proof` lo tenía.
 */
class LimiteEnviosPublicosTest extends TestCase
{
    use RefreshDatabase;

    /** Toda ruta POST pública de tienda declara un throttle. */
    public function test_ninguna_ruta_publica_de_escritura_queda_sin_limite(): void
    {
        $sinLimite = [];

        foreach (Route::getRoutes() as $ruta) {
            $nombre = (string) $ruta->getName();
            // Las rutas públicas de la tienda: `public.*` y que escriben.
            if (! str_starts_with($nombre, 'public.')) {
                continue;
            }
            if (! array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $ruta->methods())) {
                continue;
            }
            $tiene = collect($ruta->gatherMiddleware())
                ->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle'));
            if (! $tiene) {
                $sinLimite[] = $nombre;
            }
        }

        $this->assertSame([], $sinLimite,
            'Estas rutas públicas escriben sin límite de envíos: '.implode(', ', $sinLimite));
    }

    /** El límite se aplica de verdad: al pasarse, responde 429 y deja de guardar. */
    public function test_al_pasarse_del_limite_se_corta(): void
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Mi Tienda', 'slug' => 'lim-'.uniqid(), 'is_active' => true,
        ]);

        $envio = fn () => $this->post(route('public.complaints.store', $p->slug), [
            'consumer_name' => 'Juan Spam', 'document_type' => 'DNI', 'document_number' => '12345678',
            'email' => 'spam@example.com', 'product_or_service' => 'Algo',
            'type' => 'reclamo', 'detail' => 'Detalle del caso.', 'request' => 'Pido algo.',
            'terms' => 'on',
        ]);

        // El limite de esta ruta es 5 por minuto: el sexto ya no entra.
        for ($i = 0; $i < 5; $i++) {
            $envio();
        }
        $envio()->assertStatus(429);

        $this->assertLessThanOrEqual(5, \App\Models\Complaint::where('project_id', $p->id)->count(),
            'no se guarda mas alla del limite');
    }

    /** El carrito tolera el uso normal: se toca muchas veces al comprar. */
    public function test_el_carrito_no_se_corta_con_uso_normal(): void
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Mi Tienda', 'slug' => 'car-'.uniqid(), 'is_active' => true,
        ]);

        for ($i = 0; $i < 15; $i++) {
            $r = $this->post(route('public.cart.save', $p->slug), ['items' => []]);
            $this->assertNotSame(429, $r->getStatusCode(), "el carrito se corto en el intento $i");
        }
    }
}
