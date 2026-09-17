<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotQrAccessTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithModules(array $keys): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda QR',
            'slug' => 'tienda-qr-'.str()->random(6),
            'is_active' => true,
        ]);

        foreach ($keys as $key) {
            $module = Module::firstOrCreate(
                ['key' => $key],
                ['name' => ucfirst($key), 'is_active' => true]
            );
            $project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
        }

        return $project;
    }

    private function memberOf(Project $project): User
    {
        $user = User::factory()->create(['is_superadmin' => false]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'viewer',
        ]);

        return $user;
    }

    public function test_member_can_open_bots_and_scan_qr_when_crm_and_bots_are_active(): void
    {
        $project = $this->projectWithModules(['clients', 'bots']);
        $user = $this->memberOf($project);
        $dir = storage_path('app/bot-wa');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = "{$dir}/{$project->id}.json";

        try {
            file_put_contents($file, json_encode([
                'status' => 'qr',
                'qr' => 'data:image/png;base64,FANY',
                'ts' => time(),
            ]));

            $this->actingAs($user)
                ->withSession(['active_project_id' => $project->id])
                ->get(route('bot-flows.index'))
                ->assertOk()
                ->assertSee('Bots')
                ->assertSee('Línea de WhatsApp')
                ->assertDontSee('Activar Bot Comercial');

            $this->actingAs($user)
                ->withSession(['active_project_id' => $project->id])
                ->getJson(route('bot-flows.wa-status'))
                ->assertOk()
                ->assertJson([
                    'status' => 'qr',
                    'qr' => 'data:image/png;base64,FANY',
                ]);
        } finally {
            @unlink($file);
        }
    }

    public function test_member_cannot_access_qr_without_bots_module(): void
    {
        $project = $this->projectWithModules(['clients']);
        $user = $this->memberOf($project);

        $this->actingAs($user)
            ->withSession(['active_project_id' => $project->id])
            ->getJson(route('bot-flows.wa-status'))
            ->assertForbidden();
    }

    public function test_qr_member_cannot_change_bot_configuration(): void
    {
        $project = $this->projectWithModules(['clients', 'bots']);
        $user = $this->memberOf($project);

        $this->actingAs($user)
            ->withSession(['active_project_id' => $project->id])
            ->post(route('bot-flows.plantilla-comercial'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('bot-flows.editor.new'))
            ->assertForbidden();
    }
}
