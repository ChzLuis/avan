<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios de carritos abandonados: cada hora
Schedule::command('carts:remind --hours=1')->hourly();

// Expirar demos vencidas: cada día a las 2am
Schedule::job(new \App\Jobs\ExpireDemos)->dailyAt('02:00');
