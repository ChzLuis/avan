<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** El Constructor pinta el historial y sigue abriendo sin errores. */
class ConstructorHistorialVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_constructor_muestra_el_bloque_de_versiones(): void
    {
        $p = Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Mi Tienda', 'slug' => 'cv-'.uniqid(), 'is_active' => true,
        ]);

        $html = $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->get(route('settings.builder'))->assertOk()->getContent();

        $this->assertStringContainsString('Versiones publicadas', $html);
        $this->assertStringContainsString('historialPublicaciones()', $html);
        // Las rutas tienen que viajar al componente o los botones no hacen nada.
        $this->assertStringContainsString('versiones', $html);
        $this->assertStringContainsString('restaurar', $html);
        // El aviso de confirmacion usa el popup del panel, no el del navegador.
        $this->assertStringContainsString('window.__confirm', $html);
        $this->assertStringNotContainsString('confirm(\'Tu tienda', $html);
    }
}
