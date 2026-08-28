<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Borrar un negocio es irreversible: solo el DUEÑO o un superadmin. Antes
 * `ProjectController::destroy` solo comprobaba pertenencia (`authorizeProject`),
 * así que un miembro de solo lectura podía eliminar la empresa entera.
 */
class ProjectDestroyGuardTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['is_superadmin' => 0]);
        $this->project = Project::create([
            'owner_id' => $this->owner->id,
            'name' => 'Negocio QA', 'slug' => 'negocio-qa', 'category' => 'retail', 'is_active' => true,
        ]);
    }

    public function test_un_miembro_no_dueno_no_puede_borrar_el_negocio(): void
    {
        $miembro = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $miembro->id, 'role' => 'viewer']);

        $this->actingAs($miembro)
            ->delete(route('projects.destroy', $this->project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $this->project->id]);
    }

    public function test_el_dueno_si_puede_borrar_su_negocio(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('projects.destroy', $this->project))
            ->assertRedirect();

        $this->assertDatabaseMissing('projects', ['id' => $this->project->id]);
    }

    public function test_un_superadmin_puede_borrar_cualquier_negocio(): void
    {
        $super = User::factory()->create(['is_superadmin' => 1]);

        $this->actingAs($super)
            ->delete(route('projects.destroy', $this->project))
            ->assertRedirect();

        $this->assertDatabaseMissing('projects', ['id' => $this->project->id]);
    }
}
