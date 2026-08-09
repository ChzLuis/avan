<?php

namespace Tests\Feature;

use App\Http\Controllers\SettingsController;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class QrSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function project(): array
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Tienda QR',
            'slug' => 'tienda-qr',
            'is_active' => true,
        ]);
        $this->actingAs($owner);
        app()->instance('active_project', $project);

        return [$owner, $project];
    }

    private function useRequest(string $url): void
    {
        $request = Request::create($url, 'GET');
        app()->instance('request', $request);
        app('url')->setRequest($request);
    }

    public function test_qr_uses_the_current_public_domain_and_rejects_internal_hosts(): void
    {
        [, $project] = $this->project();
        config(['app.url' => 'http://localhost']);

        $this->useRequest('https://arindg.com/bixoadmin/settings/qr');
        $publicView = app(SettingsController::class)->qr();
        $this->assertSame('https://arindg.com/tienda-qr', $publicView->getData()['baseUrl']);
        $this->assertTrue($publicView->getData()['isPublicUrl']);

        $this->useRequest('http://127.0.0.1:8000/bixoadmin/settings/qr');
        $internalView = app(SettingsController::class)->qr();
        $this->assertSame('', $internalView->getData()['baseUrl']);
        $this->assertFalse($internalView->getData()['isPublicUrl']);

        $project->update(['custom_domain' => 'tienda.example.com']);
        $customView = app(SettingsController::class)->qr();
        $this->assertSame('https://tienda.example.com', $customView->getData()['baseUrl']);
        $this->assertTrue($customView->getData()['isPublicUrl']);
    }

    public function test_qr_customization_is_saved_for_only_the_active_project(): void
    {
        [, $project] = $this->project();
        $other = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otra tienda',
            'slug' => 'otra-tienda',
            'is_active' => true,
        ]);

        $request = Request::create('/bixoadmin/settings/qr', 'POST', [
            'qr_mode' => 'catalog',
            'qr_table_count' => 12,
            'qr_reception' => 'auto',
            'qr_payment' => 'cashier',
            'qr_size' => 900,
            'qr_margin' => 4,
            'qr_foreground' => '#123456',
            'qr_background' => '#ffffff',
            'qr_header_color' => '#2563eb',
            'qr_top_text' => 'Visita nuestra tienda',
            'qr_bottom_text' => 'Escanea para comprar',
            'qr_share_message' => 'Conoce nuestro catálogo',
            'qr_preset' => 'brand',
            'qr_quality' => 'high',
            'qr_show_logo' => '1',
            'qr_show_url' => '0',
            'qr_schedule' => ['monday' => ['enabled' => '1', 'start' => '09:00', 'end' => '18:00']],
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(SettingsController::class)->updateQr($request);
        $this->assertTrue($response->getData(true)['ok']);
        $this->assertSame('900', $project->setting('qr_size'));
        $this->assertSame('#123456', $project->setting('qr_foreground'));
        $this->assertSame('1', $project->setting('qr_show_logo'));
        $this->assertSame('0', $project->setting('qr_show_url'));
        $this->assertSame('09:00', data_get(json_decode($project->setting('qr_schedule'), true), 'monday.start'));
        $this->assertNull($other->setting('qr_size'));
    }
}
