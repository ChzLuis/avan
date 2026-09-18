<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Codigo que vivia SOLO en ARIN (ramas de otras sesiones desplegadas sin
 * fusionar) y que la mudanza a modulos piso el 2026-09-17, porque la puerta
 * de deriva no comparaba archivos RENOMBRADOS. Restaurado el 2026-09-18 y
 * vigilado aqui para que no vuelva a perderse.
 */
class DerivaRestauradaTest extends TestCase
{
    use RefreshDatabase;

    /** El superadmin historico no tiene email: entra por username. */
    public function test_el_superadmin_entra_por_username_o_por_email(): void
    {
        User::factory()->create(['username' => 'administrator', 'email' => 'root@bixo.test', 'password' => Hash::make('clave123'), 'is_superadmin' => 1]);

        $this->post('/admin/login', ['email' => 'administrator', 'password' => 'clave123'])->assertRedirect();
        $this->assertAuthenticated();

        auth()->logout();
        $this->post('/admin/login', ['email' => 'root@bixo.test', 'password' => 'clave123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    /** Privacidad y terminos existen en toda tienda, con texto base si no se personalizaron. */
    public function test_las_paginas_legales_de_la_tienda_responden(): void
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda legal', 'slug' => 'tienda-legal-' . uniqid(), 'is_active' => true,
        ]);

        $this->get("/{$p->slug}/privacidad")->assertOk()->assertSee('Ley N.º 29733', false);
        $this->get("/{$p->slug}/terminos")->assertOk()->assertSee('Libro de Reclamaciones');
        $this->get("/{$p->slug}/libro-reclamaciones")->assertOk();
    }
}
