<?php

namespace App\Jobs;

use App\Models\DemoRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireDemos implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DemoRequest::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->with('project')
            ->get()
            ->each(function (DemoRequest $demo) {
                $demo->project?->update(['is_active' => false]);
                $demo->update(['status' => 'expired']);
                \Log::info("Demo expirada: #{$demo->id} — {$demo->business_name}");
            });
    }
}
