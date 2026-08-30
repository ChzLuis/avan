<?php

namespace Tests\Feature;

use App\Models\AccessEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reestructuración 2026-08-30 — Bloque 1: BIXO Control ordenado.
 *
 * El Control (/admin) es EXCLUSIVO de Eskala/superadmin y su menú se agrupa
 * (Empresas / Licencias / Usuarios / Soporte / Imports / Configuración) solo
 * con lo que existe. La auditoría es de SOLO LECTURA: el Control observa,
 * no edita datos del tenant (ADR-002).
 */
class ControlNavegacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_control_es_solo_de_superadmin(): void
    {
        $comun = User::factory()->create(['is_superadmin' => 0]);

        // El middleware superadmin expulsa al login del Control (no filtra
        // contenido): lo que se garantiza es que un usuario común NUNCA ve
        // una página del Control.
        $this->actingAs($comun)->get('/admin')->assertRedirect(route('admin.login'));
        $this->actingAs($comun)->get('/admin/auditoria')->assertRedirect(route('admin.login'));
    }

    public function test_la_auditoria_muestra_el_menu_agrupado_y_los_eventos(): void
    {
        $admin = User::factory()->create(['is_superadmin' => 1]);
        $project = Project::create([
            'owner_id' => $admin->id, 'name' => 'Tenant QA',
            'slug' => 'tenant-qa-control', 'category' => 'retail', 'is_active' => true,
        ]);
        AccessEvent::create([
            'project_id' => $project->id, 'actor_id' => $admin->id,
            'action' => 'impersonate', 'role_name' => 'superadmin',
            'ip' => '127.0.0.1', 'created_at' => now(),
        ]);

        $res = $this->actingAs($admin)->get('/admin/auditoria')->assertOk();

        // El menú agrupado del Control, visible en la misma página.
        foreach (['Empresas / tenants', 'Demos', 'Licencias y asientos',
                  'Usuarios globales', 'Auditoría de accesos',
                  'Cargas masivas', 'Configuración global'] as $entrada) {
            $res->assertSee($entrada);
        }

        // Y el evento de impersonación aparece en el listado.
        $res->assertSee('impersonate');
    }

    public function test_las_empresas_ofrecen_la_entrada_auditada_al_workspace(): void
    {
        $admin = User::factory()->create(['is_superadmin' => 1]);
        Project::create([
            'owner_id' => $admin->id, 'name' => 'Tenant QA',
            'slug' => 'tenant-qa-imp', 'category' => 'retail', 'is_active' => true,
        ]);

        // El botón "Entrar como" apunta a la vía que deja rastro (ADR-003).
        $this->actingAs($admin)->get('/admin/projects')
            ->assertOk()
            ->assertSee('Entrar como')
            ->assertSee('/bixoadmin/entrar-como/', false);
    }
}
